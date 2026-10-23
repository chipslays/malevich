<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Malevich\Malevich;

/**
 * Reads the options of every anonymous Blade component from its source,
 * without rendering it: which values each option accepts.
 *
 * Only literal declarations can be read. Whatever is not literal makes the
 * affected part unknown, and unknown parts are never checked - the result
 * may be incomplete, but it is never wrong.
 *
 * @internal
 */
final class ComponentScanner
{
    private const MAX_INCLUDE_DEPTH = 5;

    public function __construct(
        private readonly Filesystem $files,
        private readonly FileViewFinder $finder,
    ) {}

    /**
     * Components that declare at least one checkable option, by tag name
     * without the `x-` (e.g. "button", "forms.input", "ui::badge").
     *
     * @param  bool  $all  Also every other component that uses @attributes, with
     *                     the options that could be read (none for a fresh one).
     * @return array<string, array{file: string, options: array<string, list<string>>, targets?: list<string>}>
     */
    public function components(bool $all = false): array
    {
        $components = [];

        foreach ($this->directories() as [$directory, $prefix]) {
            foreach ($this->files->allFiles($directory) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $component = $this->component($file->getPathname());

                if ($component === null && $all && $this->usesMalevich($file->getPathname())) {
                    $component = ['file' => $file->getPathname(), 'options' => []];
                }

                if ($component === null) {
                    continue;
                }

                foreach ($this->tags($file->getRelativePathname(), $prefix) as $tag) {
                    $components[$tag] ??= $component;
                }
            }
        }

        ksort($components);

        return $components;
    }

    /**
     * The tag name a component file is used by, without the `x-`
     * (e.g. "button", "forms.input", "ui::badge"), or null when the file is
     * not in a components folder.
     */
    public function nameOf(string $path): ?string
    {
        $path = realpath($path);

        if ($path === false) {
            return null;
        }

        foreach ($this->directories() as [$directory, $prefix]) {
            if (str_starts_with($path, $directory.DIRECTORY_SEPARATOR) && str_ends_with($path, '.blade.php')) {
                $tags = $this->tags(substr($path, strlen($directory) + 1), $prefix);

                // `card/index.blade.php` is used as <x-card>: the shortest name is the tag.
                return end($tags) ?: null;
            }
        }

        return null;
    }

    /**
     * What one component file declares, or null when it has nothing to check.
     *
     * @return array{file: string, options: array<string, list<string>>, targets: list<string>}|null
     */
    public function component(string $path): ?array
    {
        $declarations = new Declarations;

        try {
            $this->read($path, $declarations, 0);
        } catch (InvalidArgumentException) {
            return null; // Something every option depends on is unknown.
        }

        $options = $declarations->options();

        return $options === [] ? null : [
            'file' => $path,
            'options' => $options,
            'targets' => array_values(array_unique([Malevich::defaultTarget(), ...$declarations->targets])),
        ];
    }

    /**
     * Whether the file prints an element with @attributes (outside comments).
     */
    private function usesMalevich(string $path): bool
    {
        $template = (string) preg_replace('/@verbatim.*?@endverbatim|\{\{--.*?--\}\}/s', '', $this->files->get($path));

        return (bool) preg_match('/(?<!@)@'.preg_quote(Malevich::directive('attributes'), '/').'\b/', $template);
    }

    /**
     * @throws InvalidArgumentException When the component can't be read reliably.
     */
    private function read(string $path, Declarations $declarations, int $depth): void
    {
        $template = (string) preg_replace('/@verbatim.*?@endverbatim|\{\{--.*?--\}\}/s', '', $this->files->get($path));

        $options = Malevich::options();
        $option = Malevich::directive('option');
        $compound = Malevich::directive('compound');
        $cases = Malevich::directive('cases');
        $base = Malevich::directive('base');
        $names = [$option, $compound, $cases, $base, 'include', 'includeIf', 'includeWhen', 'includeUnless', 'includeFirst', 'each', ...$options];
        $pattern = '/(?<!@)@(?<name>'.implode('|', array_map(fn (string $name) => preg_quote($name, '/'), $names)).')\b\s*(?<arguments>'.TagScanner::PARENS.')?/';

        preg_match_all($pattern, $template, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        foreach ($matches as $match) {
            $name = (string) $match['name'];

            // Without parentheses it is not a declaration (e.g. Tailwind's `@variant dark`).
            if ($match['arguments'] === null) {
                continue;
            }

            $arguments = substr($match['arguments'], 1, -1);

            match (true) {
                $name === 'include' => $this->include($arguments, $declarations, $depth),
                // A partial that may or may not be there could declare anything.
                str_starts_with($name, 'include'), $name === 'each' => throw new InvalidArgumentException('Conditional partial.'),
                $name === $option => $this->option(null, $arguments, $declarations),
                $name === $compound => $this->compound($arguments, $declarations),
                $name === $cases => $this->cases($arguments, $declarations),
                $name === $base => $this->target(ConstantExpression::split($arguments), $declarations),
                default => $this->option($name, $arguments, $declarations),
            };
        }
    }

    /**
     * `@variant([...])`, `@size('icon', [...])`, `@option('padding', [...])`.
     */
    private function option(?string $option, string $arguments, Declarations $declarations): void
    {
        $arguments = ConstantExpression::split($arguments);

        // `@option($name, ...)`: any option might be meant.
        $option ??= self::string(array_shift($arguments));
        $this->target($arguments, $declarations);

        try {
            $map = ConstantExpression::evaluate((string) end($arguments));
        } catch (InvalidArgumentException) {
            $declarations->unchecked($option); // `@size($sizes)`

            return;
        }

        // A string instead of a map styles every value alike: any value is fine.
        is_array($map) ? $declarations->values($option, array_keys($map)) : $declarations->unchecked($option);
    }

    /**
     * `@cases('variant', ['teal' => [...], 'dark' => [...]])`.
     */
    private function cases(string $arguments, Declarations $declarations): void
    {
        $arguments = ConstantExpression::split($arguments);
        $option = self::string($arguments[0] ?? null);

        try {
            $cases = ConstantExpression::entries($arguments[1] ?? '');
        } catch (InvalidArgumentException) {
            $declarations->unchecked($option);

            return;
        }

        $declarations->values($option, array_keys($cases));

        if (isset($arguments[2])) {
            $conditions = ConstantExpression::evaluate((string) preg_replace('/^when\s*:\s*/', '', $arguments[2]));

            if (! is_array($conditions)) {
                throw new InvalidArgumentException('Unexpected @cases when conditions.');
            }

            foreach ($conditions as $condition => $expected) {
                $declarations->values((string) $condition, is_array($expected) ? array_values($expected) : [$expected]);
            }
        }

        foreach ($cases as $source) {
            try {
                $targets = ConstantExpression::entries($source);

                if (self::hasTargetClasses($targets)) {
                    $declarations->targets = [...$declarations->targets, ...self::targetNames(array_keys($targets))];
                }
            } catch (InvalidArgumentException) {
                // Classes may be dynamic even when the option values are known.
            }
        }
    }

    /**
     * Boolean class maps belong to the root. A known non-boolean value under
     * a named key identifies a target map; dynamic-only maps remain unknown.
     *
     * @param  array<array-key, string>  $entries
     */
    private static function hasTargetClasses(array $entries): bool
    {
        foreach ($entries as $key => $source) {
            if (is_int($key)) {
                continue;
            }

            try {
                if (! is_bool(ConstantExpression::evaluate($source))) {
                    return true;
                }
            } catch (InvalidArgumentException) {
                try {
                    ConstantExpression::entries($source);

                    return true;
                } catch (InvalidArgumentException) {
                    // The value's type cannot be established without executing it.
                }
            }
        }

        return false;
    }

    /**
     * `@compound(['variant' => 'primary', 'size' => ['sm', 'md']], '...')`:
     * the values it tests are valid values. If they can't be read, no option
     * of the component can be checked safely.
     */
    private function compound(string $arguments, Declarations $declarations): void
    {
        $arguments = ConstantExpression::split($arguments);

        if (count($arguments) === 3) {
            $this->target([$arguments[0], $arguments[2]], $declarations);
        }

        // `@compound([...], targets: ['default' => ..., 'leading,trailing' => ...])`.
        if (count($arguments) === 2 && preg_match('/^targets\s*:\s*(.*)$/s', $arguments[1], $named)) {
            try {
                $declarations->targets = [...$declarations->targets, ...self::targetNames(array_keys(ConstantExpression::entries($named[1])))];
            } catch (InvalidArgumentException) {
                // Dynamic target keys can't justify consuming a namespace.
            }
        }

        $conditions = ConstantExpression::evaluate($arguments[count($arguments) === 3 ? 1 : 0] ?? '');

        if (! is_array($conditions)) {
            throw new InvalidArgumentException('Unexpected @compound arguments.');
        }

        foreach ($conditions as $option => $expected) {
            $declarations->values((string) $option, is_array($expected) ? array_values($expected) : [$expected]);
        }
    }

    /**
     * Element names of target-map keys: `'leading,trailing'` is two of them.
     *
     * @param  list<int|string>  $keys
     * @return list<string>
     */
    private static function targetNames(array $keys): array
    {
        $names = [];

        foreach ($keys as $key) {
            foreach (explode(',', (string) $key) as $name) {
                $names[] = trim($name);
            }
        }

        return $names;
    }

    /**
     * Record a literal target without guessing dynamic declarations.
     *
     * @param  list<string>  $arguments
     */
    private function target(array $arguments, Declarations $declarations): void
    {
        if (count($arguments) !== 2) {
            return;
        }

        try {
            $declarations->targets[] = self::string($arguments[0]);
        } catch (InvalidArgumentException) {
            // An unknown target cannot justify consuming an external namespace.
        }
    }

    /**
     * `@include('components.partials.sizes')`: shared class maps. A partial
     * that can't be named or found makes the whole component unknown.
     */
    private function include(string $arguments, Declarations $declarations, int $depth): void
    {
        $view = self::string(ConstantExpression::split($arguments)[0] ?? null);

        if ($depth >= self::MAX_INCLUDE_DEPTH) {
            throw new InvalidArgumentException('Partials nested too deep.');
        }

        $this->read($this->finder->find($view), $declarations, $depth + 1);
    }

    /**
     * @throws InvalidArgumentException When the source is not a string literal.
     */
    private static function string(?string $source): string
    {
        $value = ConstantExpression::evaluate((string) $source);

        return is_string($value) ? $value : throw new InvalidArgumentException('Not a string.');
    }

    /**
     * Folders with anonymous components and their tag prefix.
     *
     * @return list<array{0: string, 1: string|null}>
     */
    private function directories(): array
    {
        // In the order Blade looks a tag up, so the first match wins here too:
        // anonymous namespaces, anonymous paths, then "components.*" in every view path.
        $directories = [];
        $hints = $this->finder->getHints();

        foreach (Blade::getAnonymousComponentNamespaces() as $prefix => $namespace) {
            [$hint, $directory] = array_pad(explode('::', $namespace, 2), 2, '');

            foreach ($hints[$hint] ?? [] as $root) {
                $directories[] = [rtrim($root.'/'.str_replace('.', '/', $directory), '/'), $prefix];
            }
        }

        foreach (Blade::getAnonymousComponentPaths() as $path) {
            $directories[] = [$path['path'], $path['prefix'] ?: null];
        }

        foreach ($this->finder->getPaths() as $path) {
            $directories[] = [$path.'/components', null];
        }

        $unique = [];

        foreach ($directories as [$directory, $prefix]) {
            $real = realpath($directory);

            if ($real !== false && is_dir($real)) {
                $unique[$real.'|'.$prefix] = [$real, $prefix];
            }
        }

        return array_values($unique);
    }

    /**
     * The tags a file (path relative to its components folder) is
     * available as: `forms/input.blade.php` is
     * "forms.input", `card/index.blade.php` and `card/card.blade.php` are
     * "card" as well.
     *
     * @return list<string>
     */
    private function tags(string $relativePath, ?string $prefix): array
    {
        $name = str_replace(['/', '\\'], '.', substr($relativePath, 0, -strlen('.blade.php')));
        $names = [$name];

        $segments = explode('.', $name);

        if (count($segments) > 1 && in_array(end($segments), ['index', $segments[count($segments) - 2]], true)) {
            $names[] = implode('.', array_slice($segments, 0, -1));
        }

        return array_map(fn (string $name) => ($prefix ? $prefix.'::' : '').$name, $names);
    }
}
