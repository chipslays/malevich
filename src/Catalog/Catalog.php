<?php

declare(strict_types=1);

namespace Malevich\Catalog;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use LogicException;
use Malevich\Malevich;
use Malevich\Support\ComponentScanner;
use Malevich\Support\ConstantExpression;
use Malevich\Support\TagScanner;
use Throwable;

/**
 * The component catalog: every component of the app rendered with every
 * value of its options. Built from the same static scan as malevich:lint,
 * so it needs no setup - a component shows up as soon as it declares a
 * class map.
 *
 * @internal
 */
final class Catalog
{
    public function __construct(
        private readonly ComponentScanner $scanner,
        private readonly Filesystem $files,
    ) {}

    public static function make(): self
    {
        $finder = app('view')->getFinder();

        if (! $finder instanceof FileViewFinder) {
            throw new LogicException('The Malevich catalog needs Laravel\'s file view finder to locate components.');
        }

        return new self(new ComponentScanner(new Filesystem, $finder), new Filesystem);
    }

    public static function enabled(): bool
    {
        $enabled = config('malevich.catalog.enabled');

        return $enabled === null || $enabled === '' ? app()->isLocal() : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function routes(): void
    {
        $path = trim((string) config('malevich.catalog.path', 'malevich'), '/');

        Route::middleware((array) config('malevich.catalog.middleware', ['web']))->group(function () use ($path) {
            Route::get($path, CatalogController::class)->name('malevich.catalog');
            // POST, so the web middleware checks the CSRF token: another site can't make the
            // developer's browser render Blade here.
            Route::post($path.'/preview', [CatalogController::class, 'preview'])->name('malevich.catalog.preview');
        });
    }

    /**
     * Whether the playground may run typed content as Blade. That is running
     * code, so only on a developer's machine - wherever else the catalog is
     * turned on, the slot fields still work.
     */
    public static function bladeAllowed(): bool
    {
        return app()->isLocal();
    }

    /**
     * The application's components with options, by tag name without `x-`.
     * The package's own components are left out.
     *
     * @return array<string, array{file: string, options: array<string, list<string>>}>
     */
    public function components(): array
    {
        $components = [];

        foreach ($this->scanner->components(all: true) as $name => $component) {
            if (! str_starts_with($name, 'malevich::')) {
                $components[$name] = ['file' => $component['file'], 'options' => $this->ordered($component['options'])];
            }
        }

        return $components;
    }

    /**
     * Everything the page shows for one component: the default rendering,
     * one row per option, and a matrix of the first two options.
     *
     * @param  array{file: string, options: array<string, list<string>>}  $component
     * @return array{slot: string, default: Cell, options: array<string, list<Cell>>, matrix: array{rows: string, columns: string, cells: array<string, array<string, Cell>>}|null}
     */
    public function sheet(string $name, array $component): array
    {
        $slot = $this->example($component['file']) ?? $this->label($name);
        $options = [];

        foreach ($component['options'] as $option => $values) {
            foreach ($values as $value) {
                $options[$option][] = $this->cell($name, [$option => $value], $slot);
            }
        }

        $matrix = null;
        $names = array_keys($component['options']);

        if (count($names) >= 2) {
            [$rows, $columns] = $names;
            $cells = [];

            foreach ($component['options'][$rows] as $row) {
                foreach ($component['options'][$columns] as $column) {
                    $cells[$row][$column] = $this->cell($name, [$rows => $row, $columns => $column], $slot);
                }
            }

            $matrix = ['rows' => $rows, 'columns' => $columns, 'cells' => $cells];
        }

        return [
            'slot' => $slot,
            'default' => $this->cell($name, [], $slot),
            'options' => $options,
            'matrix' => $matrix,
        ];
    }

    /**
     * The tag for these option values, rendered.
     *
     * @param  array<string, string>  $values
     */
    public function cell(string $name, array $values, ?string $slot): Cell
    {
        $tag = self::tag($name, $values, $slot);

        return $this->render($tag, $tag, $values);
    }

    /**
     * The playground: the component with the values picked in the controls
     * and the content in one of three forms:
     *
     * - example: the usage example from the component's comment (trusted, it is the app's own file);
     * - slots:   text per slot, `default` and named ones - passed as data, so it never runs as Blade;
     * - blade:   any Blade (other components, HTML, slots) - only where bladeAllowed().
     *
     * Attributes of the component (`class`, `href`, props, ...) and the class
     * of each named slot are data too, in every form. Only values the
     * component declares are used for its options.
     *
     * @param  array{file: string, options: array<string, list<string>>}  $component
     * @param  array<array-key, mixed>  $values
     * @param  array{mode?: string, slots?: array<array-key, mixed>, slotClasses?: array<array-key, mixed>, attributes?: array<array-key, mixed>, class?: string, blade?: string}  $content
     */
    public function preview(string $name, array $component, array $values, array $content = []): Cell
    {
        $picked = [];

        foreach ($component['options'] as $option => $known) {
            $value = $values[$option] ?? null;

            if (is_string($value) && in_array($value, $known, true)) {
                $picked[$option] = $value;
            }
        }

        $attributes = $content['attributes'] ?? [];

        if (($content['class'] ?? '') !== '' && ! isset($attributes['class'])) {
            $attributes['class'] = $content['class'];
        }

        $attributes = self::attributes($attributes, array_keys($component['options']));

        return match ($content['mode'] ?? 'example') {
            'slots' => $this->previewSlots($name, $picked, $attributes, $content['slots'] ?? [], $content['slotClasses'] ?? []),
            'blade' => $this->previewBlade($name, $picked, $attributes, mb_substr($content['blade'] ?? '', 0, 5000)),
            default => $this->previewBlade($name, $picked, $attributes, $this->example($component['file']) ?? $this->label($name), trusted: true),
        };
    }

    /**
     * The attributes typed into the playground: valid names only, no option
     * names (the option controls set those), and never `:bound` or `@...`,
     * which would run PHP. "true" is a flag, "false" turns it off.
     *
     * @param  array<array-key, mixed>  $attributes
     * @param  list<string>  $options
     * @return array<string, string>
     */
    private static function attributes(array $attributes, array $options): array
    {
        $clean = [];

        foreach ($attributes as $attribute => $value) {
            if (is_string($attribute) && is_string($value) && preg_match('/^[a-zA-Z][\w\-.:]*$/', $attribute) && ! in_array($attribute, $options, true)) {
                $value = trim(mb_substr($value, 0, 300));

                if ($value !== '' || $attribute !== 'class') {
                    $clean[$attribute] = $value;
                }
            }
        }

        return array_slice($clean, 0, 30, true);
    }

    /**
     * The attributes as Blade (values from data) and as shown in the tag.
     *
     * @param  array<string, string>  $attributes
     * @return array{0: string, 1: string}
     */
    private static function attributeStrings(array $attributes): array
    {
        $template = '';
        $display = '';

        foreach ($attributes as $attribute => $value) {
            [$template, $display] = match ($value) {
                'true', '' => [$template." {$attribute}", $display." {$attribute}"],
                'false' => [$template." :{$attribute}=\"false\"", $display." :{$attribute}=\"false\""],
                default => [$template." {$attribute}=\"{{ \$__catalogAttributes['{$attribute}'] }}\"", $display." {$attribute}=\"".str_replace('"', '&quot;', $value).'"'],
            };
        }

        return [$template, $display];
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $attributes
     */
    private function previewBlade(string $name, array $values, array $attributes, string $blade, bool $trusted = false): Cell
    {
        [$template, $display] = self::attributeStrings($attributes);
        $tag = self::tag($name, $values, $blade, $display);

        if (! $trusted && ! self::bladeAllowed()) {
            return new Cell($tag, $values, null, 'Blade content runs only in the local environment. Use the slot fields instead.');
        }

        return $this->render(self::tag($name, $values, $blade, $template), $tag, $values, ['__catalogAttributes' => $attributes]);
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $attributes
     * @param  array<array-key, mixed>  $slots
     * @param  array<array-key, mixed>  $slotClasses
     */
    private function previewSlots(string $name, array $values, array $attributes, array $slots, array $slotClasses): Cell
    {
        $template = [];
        $display = [];
        $texts = [];
        $classes = [];

        foreach ($slots as $slot => $text) {
            if (! is_string($slot) || ! preg_match('/^[a-zA-Z][\w\-]*$/', $slot) || ! is_string($text)) {
                continue;
            }

            $texts[$slot] = mb_substr($text, 0, 1000);
            $class = is_string($slotClasses[$slot] ?? null) ? trim(mb_substr($slotClasses[$slot], 0, 300)) : '';

            if ($slot === 'default' || (trim($text) === '' && $class === '')) {
                continue;
            }

            $classes[$slot] = $class;
            $template[] = "<x-slot:{$slot}".($class === '' ? '' : " class=\"{{ \$__catalogSlotClasses['{$slot}'] }}\"").">{{ \$__catalogSlots['{$slot}'] }}</x-slot:{$slot}>";
            $display[] = "<x-slot:{$slot}".($class === '' ? '' : ' class="'.str_replace('"', '&quot;', $class).'"').">{$text}</x-slot:{$slot}>";
        }

        $default = trim($texts['default'] ?? '');

        if ($default !== '') {
            $template[] = "{{ \$__catalogSlots['default'] }}";
            $display[] = $default;
        }

        $inner = fn (array $lines) => match (count($lines)) {
            0 => null,
            1 => $lines[0],
            default => "\n    ".implode("\n    ", $lines)."\n",
        };
        [$attributeTemplate, $attributeDisplay] = self::attributeStrings($attributes);

        return $this->render(
            self::tag($name, $values, $inner($template), $attributeTemplate),
            self::tag($name, $values, $inner($display), $attributeDisplay),
            $values,
            ['__catalogSlots' => $texts, '__catalogSlotClasses' => $classes, '__catalogAttributes' => $attributes],
        );
    }

    /**
     * The props a component declares in `@props([...])`, with their default
     * when it is a plain value - for the playground's "add a prop" buttons.
     * Options are left out: they have controls of their own.
     *
     * @param  array{file: string, options: array<string, list<string>>}  $component
     * @return array<string, string>
     */
    public function props(array $component): array
    {
        $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $this->files->get($component['file']));

        if (! preg_match('/(?<!@)@props\s*(?<arguments>'.TagScanner::PARENS.')/', $source, $match)) {
            return [];
        }

        try {
            $props = ConstantExpression::evaluate(substr($match['arguments'], 1, -1));
        } catch (InvalidArgumentException) {
            return [];
        }

        $found = [];

        foreach (is_array($props) ? $props : [] as $key => $default) {
            [$prop, $default] = is_int($key) ? [$default, null] : [$key, $default];

            if (is_string($prop) && ! isset($component['options'][$prop]) && ! in_array($prop, Malevich::options(), true)) {
                $found[$prop] = match (true) {
                    is_bool($default) => $default ? 'true' : 'false',
                    is_scalar($default) => (string) $default,
                    default => '',
                };
            }
        }

        return $found;
    }

    /**
     * The class of every named slot in a usage example: `<x-slot:title class="...">`.
     *
     * @return array<string, string>
     */
    public function slotClassesOf(string $content): array
    {
        preg_match_all('/<x-slot(?::([\w\-.]+)|\s+name=["\']([\w\-.]+)["\'])([^>]*)>/', $content, $matches, PREG_SET_ORDER);

        $classes = [];

        foreach ($matches as $match) {
            if (preg_match('/\bclass=["\']([^"\']*)["\']/', $match[3], $class)) {
                $classes[$match[1] !== '' ? $match[1] : $match[2]] = $class[1];
            }
        }

        return $classes;
    }

    /**
     * The content of a usage example split into slots, for the playground's
     * fields: `default` (the text outside named slots) first, then every
     * `<x-slot:name>` / `<x-slot name="...">`.
     *
     * @return array<string, string>
     */
    public function slotsOf(string $content): array
    {
        $named = [];
        $rest = preg_replace_callback(
            '/<x-slot(?::([\w\-.]+)|\s+name=["\']([\w\-.]+)["\'])[^>]*>(.*?)<\/x-slot(?::[\w\-.]+)?\s*>/s',
            function (array $match) use (&$named): string {
                $named[$match[1] !== '' ? $match[1] : $match[2]] = trim($match[3]);

                return '';
            },
            $content,
        ) ?? $content;

        return ['default' => trim((string) preg_replace('/\s*\n\s*/', ' ', $rest))] + $named;
    }

    /**
     * Tags to insert into the Blade editor: each component's usage example,
     * or a plain tag.
     *
     * @param  array<string, array{file: string, options: array<string, list<string>>}>  $components
     * @return array<string, string>
     */
    public function snippets(array $components): array
    {
        $snippets = [];

        foreach ($components as $name => $component) {
            $snippets[$name] = self::tag($name, [], $this->example($component['file']) ?? $this->label($name));
        }

        return $snippets;
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $data
     */
    private function render(string $template, string $tag, array $values, array $data = []): Cell
    {
        try {
            return new Cell($tag, $values, trim(Blade::render($template, $data)));
        } catch (Throwable $e) {
            return new Cell($tag, $values, null, $e->getPrevious()?->getMessage() ?? $e->getMessage());
        }
    }

    /**
     * @param  array<string, string>  $values
     */
    private static function tag(string $name, array $values, ?string $slot, string $extra = ''): string
    {
        $attributes = '';

        foreach ($values as $option => $value) {
            $attributes .= match ($value) {
                'true' => " {$option}",
                'false' => " :{$option}=\"false\"",
                default => " {$option}=\"".e($value).'"',
            };
        }

        return $slot === null || trim($slot) === ''
            ? "<x-{$name}{$attributes}{$extra} />"
            : "<x-{$name}{$attributes}{$extra}>{$slot}</x-{$name}>";
    }

    /**
     * The slot text when the component has no usage example: "Button".
     */
    private function label(string $name): string
    {
        return Str::headline((string) Str::afterLast(Str::afterLast($name, '::'), '.'));
    }

    /**
     * The content of the usage example in the component's leading comment:
     *
     *     {{-- <x-alert color="red"><x-slot:title>Oops</x-slot:title>Declined.</x-alert> --}}
     *
     * An empty string for a self-closing example, null without one.
     */
    public function example(string $file): ?string
    {
        if (! preg_match('/^\s*\{\{--(.*?)--\}\}/s', $this->files->get($file), $comment)) {
            return null;
        }

        if (! preg_match('/<x-([\w\-:.]+)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*?(\/>|>(.*?)<\/x-\1\s*>)/s', $comment[1], $tag)) {
            return null;
        }

        $slot = $tag[3] ?? '';

        return $tag[2] === '/>' ? '' : trim(preg_replace('/^[ \t]+/m', '', $slot) ?? $slot);
    }

    /**
     * What goes into the page's <head> to load the app's CSS.
     */
    public function head(): string
    {
        $head = config('malevich.catalog.head');

        if (is_string($head)) {
            return Blade::render($head);
        }

        $vite = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
        $entries = self::viteEntries(base_path());

        if (! $vite || $entries === []) {
            return '';
        }

        return Blade::render('@vite(['.implode(', ', array_map(fn (string $entry) => var_export($entry, true), $entries)).'])');
    }

    /**
     * The project's own Vite entry points - the same CSS (with its theme,
     * custom colors and fonts) and JS (Alpine, ...) the app's pages load.
     * Read from `input` in vite.config; without it, the Laravel defaults
     * that exist.
     *
     * @return list<string>
     */
    public static function viteEntries(string $base): array
    {
        $entries = [];

        foreach (['js', 'ts', 'mjs', 'mts', 'cjs'] as $extension) {
            $config = "{$base}/vite.config.{$extension}";

            if (is_file($config) && preg_match('/\binput\s*:\s*(\[[^\]]*\]|\'[^\']*\'|"[^"]*")/', (string) file_get_contents($config), $input)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $input[1], $strings);
                $entries = $strings[1];

                break;
            }
        }

        $entries = $entries === [] ? ['resources/css/app.css', 'resources/js/app.js'] : $entries;

        return array_values(array_filter($entries, fn (string $entry) => is_file("{$base}/{$entry}")));
    }

    /**
     * The configured options first (variant, size, color), then the rest.
     *
     * @param  array<string, list<string>>  $options
     * @return array<string, list<string>>
     */
    private function ordered(array $options): array
    {
        $order = array_flip(Malevich::options());

        uksort($options, fn (string $a, string $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);

        return $options;
    }
}
