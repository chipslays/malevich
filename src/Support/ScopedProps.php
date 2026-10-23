<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use WeakMap;

/**
 * Adapts scoped input to declared Blade props without discarding its metadata.
 *
 * @internal
 */
final class ScopedProps
{
    public const SCOPE_VARIABLE = '__malevichScopedAttributes';

    /**
     * @var WeakMap<ComponentAttributeBag, array<string, mixed>>|null
     */
    private static ?WeakMap $attributes = null;

    /**
     * @param  array<array-key, mixed>  $props
     * @param  array<string, mixed>  $data  Blade's original component data, before attribute escaping.
     * @return array{ComponentAttributeBag, array<string, mixed>, array<string, mixed>}
     */
    public static function extract(ComponentAttributeBag $attributes, array $props, array $data = []): array
    {
        $declared = ComponentAttributeBag::extractPropNames($props);
        $remaining = $attributes->getAttributes();
        $aliases = [];
        $scoped = self::attributes($attributes);

        foreach ($remaining as $name => $value) {
            $parsed = TargetAttribute::parse((string) $name);

            if ($parsed === null || ! in_array($alias = TargetAttribute::propName($parsed), $declared, true)) {
                continue;
            }

            $dataName = Str::camel((string) $name);
            $aliases[$alias] = array_key_exists($dataName, $data) ? $data[$dataName] : $value;
            $scoped[$name] = $value;
            unset($remaining[$name]);
        }

        return [$aliases === [] ? $attributes : new ComponentAttributeBag($remaining), $aliases, $scoped];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function remember(ComponentAttributeBag $bag, array $attributes): void
    {
        if ($attributes === []) {
            return;
        }

        self::$attributes ??= new WeakMap;
        self::$attributes[$bag] = $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributes(ComponentAttributeBag $bag): array
    {
        return self::$attributes[$bag] ?? [];
    }

    /**
     * Props whose default is a boolean: `loading="false"` on the tag is the
     * string "false", which a template's `@if ($loading)` would take as true.
     *
     * @param  array<array-key, mixed>  $props
     * @return list<string>
     */
    public static function booleans(array $props): array
    {
        $names = [];

        foreach ($props as $name => $default) {
            if (is_string($name) && is_bool($default)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * "true" and "false" as booleans, as Malevich reads them for class maps.
     */
    public static function boolean(mixed $value): mixed
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }

    public static function compile(string $template): string
    {
        if (! str_contains($template, '@props')) {
            return $template;
        }

        $pattern = '/(?(DEFINE)'.TagScanner::PARENS.')'
            .'@verbatim.*?@endverbatim(*SKIP)(*FAIL)'
            .'|\{\{--.*?--\}\}(*SKIP)(*FAIL)'
            .'|<\?(?:php|=).*?\?>(*SKIP)(*FAIL)'
            .'|(?<!@)@php\s*(?&parens)(*SKIP)(*FAIL)'
            .'|(?<!@)@php\b.*?@endphp(*SKIP)(*FAIL)'
            .'|(?<!@)@props\s*(?&parens)/s';

        return preg_replace_callback($pattern, function (array $match): string {
            $expression = substr($match[0], (int) strpos($match[0], '(') + 1, -1);

            if (trim($expression) === '$__malevichPropDefaults') {
                return $match[0];
            }

            return '<?php '
                .'$__malevichPropDefaults = '.$expression.'; '
                .'[$attributes, $__malevichPropAliases, $'.self::SCOPE_VARIABLE.'] = \\'.self::class.'::extract('
                .'$attributes ?? new \\Illuminate\\View\\ComponentAttributeBag, $__malevichPropDefaults, $__data ?? []); '
                .'foreach ($__malevichPropAliases as $__malevichPropName => $__malevichPropValue) { ${$__malevichPropName} = $__malevichPropValue; } '
                .'unset($__malevichPropAliases, $__malevichPropName, $__malevichPropValue); ?>'
                .'@props($__malevichPropDefaults)'
                .'<?php \\'.self::class.'::remember($attributes, $'.self::SCOPE_VARIABLE.'); '
                .'foreach (\\'.self::class.'::booleans($__malevichPropDefaults) as $__malevichPropName) { ${$__malevichPropName} = \\'.self::class.'::boolean(${$__malevichPropName} ?? null); } '
                .'unset($__malevichPropDefaults, $__malevichPropName); ?>';
        }, $template) ?? $template;
    }
}
