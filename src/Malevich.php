<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Malevich\Support\RecipeRegistry;

/**
 * Package entry point: the runtime behind the Blade directives, plus the
 * few global hooks an application may want to customize.
 */
final class Malevich
{
    /**
     * Template variable that holds the component's Recipe.
     */
    public const SCOPE_VARIABLE = '__malevichRecipe';

    /**
     * @var (Closure(string): string)|null
     */
    private static ?Closure $classMerger = null;

    /**
     * Post-process every resolved class string, e.g. to resolve Tailwind
     * conflicts so that classes passed from the outside win:
     *
     *     Malevich::mergeClassesUsing(fn (string $classes) => TailwindMerge::merge($classes));
     *
     * Pass null to restore the default (de-duplication only).
     *
     * @param  (Closure(string): string)|null  $merger
     */
    public static function mergeClassesUsing(?Closure $merger): void
    {
        self::$classMerger = $merger;
    }

    public static function mergeClasses(string $classes): string
    {
        return self::$classMerger ? (self::$classMerger)($classes) : $classes;
    }

    public static function recipe(ComponentAttributeBag $attributes): Recipe
    {
        return RecipeRegistry::for($attributes);
    }

    /**
     * Runtime of the `@ui` directive. Axis values are picked up from the
     * template scope (your @props) and from the attribute bag, so the
     * template never has to repeat them.
     *
     * A named slot with the same name as the target (`@ui('title')` and
     * `<x-slot:title>`) is picked up automatically. Pass `slot:` to use a
     * differently named slot, or `slot: false` to opt out.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     * @param  array<string, mixed>  $merge  Extra attributes, as with $attributes->merge().
     */
    public static function ui(
        ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
    ): Element {
        if ($slot === null && $target !== null && ($scope[$target] ?? null) instanceof ComponentSlot) {
            $slot = $scope[$target];
        }

        $recipe = ($scope[self::SCOPE_VARIABLE] ?? null) instanceof Recipe ? $scope[self::SCOPE_VARIABLE] : null;

        return (new Element($attributes, $target, $scope, $recipe))
            ->slot($slot)
            ->merge($merge);
    }

    /**
     * Runtime of `@hasUi('target')`: whether the element ends up with any
     * classes. Lets a template skip a part that exists only in some variants.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     * @param  array<string, mixed>  $merge  Extra attributes, as with @ui.
     */
    public static function has(
        ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
    ): bool {
        return self::ui($attributes, $scope, $target, $slot, $merge)->toClasses() !== '';
    }

    public static function defaultTarget(): string
    {
        return config('malevich.default_target') ?: 'default';
    }

    public static function renderDirective(): string
    {
        return config('malevich.render_directive') ?: 'ui';
    }

    public static function themeDirective(): string
    {
        return config('malevich.theme_directive') ?: 'theme';
    }

    public static function hasDirective(): string
    {
        return config('malevich.has_directive') ?: 'hasUi';
    }

    /**
     * Directive names from config, e.g. ['variant', 'size', 'color'].
     *
     * @return list<string>
     */
    public static function directives(): array
    {
        return array_values(array_filter((array) config('malevich.directives', []), is_string(...)));
    }

    /**
     * The Blade tag a component file is available as, e.g. "x-forms.input".
     */
    public static function componentTag(string $name): string
    {
        $prefix = config('malevich.components.prefix');

        return 'x-'.($prefix ? $prefix.'::' : '').str_replace(['/', '\\'], '.', $name);
    }

    /**
     * Reset global state. Mainly useful in tests.
     */
    public static function flush(): void
    {
        self::$classMerger = null;
        RecipeRegistry::flush();
    }
}
