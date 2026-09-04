<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\View\ComponentAttributeBag;
use WeakMap;

/**
 * Central, per-request store that associates a component's attribute bag
 * with the directive configuration (e.g. @variant, @color, @size) declared
 * for it in a Blade template, grouped by "target".
 *
 * A WeakMap keys the store on the ComponentAttributeBag instance itself, so
 * registered configuration is garbage collected automatically once the
 * owning component goes out of scope — the package never needs to clean up
 * after a render.
 */
final class DirectiveRegistry
{
    /**
     * Directive configuration keyed by [attribute bag][target][directive].
     *
     * @var WeakMap<ComponentAttributeBag, array<string, array<string, mixed>>>|null
     */
    private static ?WeakMap $directives = null;

    /**
     * Named, reusable directive configurations registered via @preset.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $presets = [];

    /**
     * Register a directive's class map for a given component instance.
     *
     * Accepts either:
     *
     *     DirectiveRegistry::registerDirective($attributes, 'variant', [
     *         'primary' => 'bg-blue-500 text-white',
     *     ]);
     *
     * or, to scope the configuration to a specific target (a named,
     * separately styled part of the component):
     *
     *     DirectiveRegistry::registerDirective($attributes, 'variant', 'track', [
     *         'primary' => 'bg-blue-500 text-white',
     *     ]);
     *
     * @param  ComponentAttributeBag  $attributes  The attribute bag of the component instance the directive was declared in.
     * @param  string  $directive  The directive name (e.g. "variant", "color", "size").
     * @param  mixed  ...$args  Either [config] or [target, config].
     */
    public static function registerDirective(ComponentAttributeBag $attributes, string $directive, mixed ...$args): void
    {
        [$target, $config] = static::resolveTargetAndConfig($args);

        $directives = static::directives();

        $directives[$attributes] ??= [];
        $directives[$attributes][$target] ??= [];
        $directives[$attributes][$target][$directive] = $config;
    }

    /**
     * Retrieve the directive configuration registered for a given
     * component instance and target.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getDirectives(ComponentAttributeBag $attributes, string $target): array
    {
        return static::directives()[$attributes][$target] ?? [];
    }

    /**
     * Register a named, reusable set of directive values.
     *
     * @param  array<string, mixed>  $config
     */
    public static function registerPreset(string $name, array $config): void
    {
        static::$presets[$name] = $config;
    }

    /**
     * Retrieve a previously registered preset by name.
     *
     * @return array<string, mixed>
     */
    public static function getPreset(string $name): array
    {
        return static::$presets[$name] ?? [];
    }

    /**
     * Normalize the variadic arguments accepted by registerDirective() into
     * an explicit [target, config] pair, falling back to the configured
     * default target when none is given.
     *
     * @param  array<int, mixed>  $args
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function resolveTargetAndConfig(array $args): array
    {
        if (count($args) > 1) {
            return [$args[0], $args[1]];
        }

        return [config('malevich.default_target', 'default'), $args[0]];
    }

    /**
     * @return WeakMap<ComponentAttributeBag, array<string, array<string, mixed>>>
     */
    private static function directives(): WeakMap
    {
        return static::$directives ??= new WeakMap;
    }
}
