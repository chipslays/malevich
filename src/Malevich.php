<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use LogicException;
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
     * Template variable that holds the element `@ui` resolved for a component tag.
     */
    public const UI_VARIABLE = '__malevichUi';

    /**
     * @var (Closure(string): string)|null
     */
    private static ?Closure $classMerger = null;

    /**
     * Config values read on every render, resolved once.
     *
     * @var array{default_target: string, render_directive: string, cases_directive: string, has_directive: string, directives: list<string>}|null
     */
    private static ?array $config = null;

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

    public static function recipe(?ComponentAttributeBag $attributes): Recipe
    {
        return RecipeRegistry::for($attributes ?? self::outsideComponent());
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
        ?ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
    ): Element {
        $attributes ??= self::outsideComponent();

        if ($slot === null && $target !== null && ($scope[$target] ?? null) instanceof ComponentSlot) {
            $slot = $scope[$target];
        }

        $recipe = ($scope[self::SCOPE_VARIABLE] ?? null) instanceof Recipe ? $scope[self::SCOPE_VARIABLE] : null;

        return (new Element($attributes, $target, $scope, $recipe))
            ->slot($slot)
            ->merge($merge);
    }

    /**
     * Runtime of `@has('target')`: whether the element ends up with any
     * classes. Lets a template skip a part that exists only in some variants.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     * @param  array<string, mixed>  $merge  Extra attributes, as with @ui.
     */
    public static function has(
        ?ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
    ): bool {
        return self::ui($attributes, $scope, $target, $slot, $merge)->toClasses() !== '';
    }

    public static function defaultTarget(): string
    {
        return self::config()['default_target'];
    }

    public static function renderDirective(): string
    {
        return self::config()['render_directive'];
    }

    public static function casesDirective(): string
    {
        return self::config()['cases_directive'];
    }

    public static function hasDirective(): string
    {
        return self::config()['has_directive'];
    }

    /**
     * Directive names from config, e.g. ['variant', 'size', 'color'].
     *
     * @return list<string>
     */
    public static function directives(): array
    {
        return self::config()['directives'];
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
     * Forget the config values read so far, so changes made with config()
     * at runtime are picked up. The service provider calls it on boot.
     */
    public static function flushConfig(): void
    {
        self::$config = null;
    }

    /**
     * Reset global state. Mainly useful in tests.
     */
    public static function flush(): void
    {
        self::$classMerger = null;
        self::flushConfig();
        RecipeRegistry::flush();
    }

    /**
     * @return array{default_target: string, render_directive: string, cases_directive: string, has_directive: string, directives: list<string>}
     */
    private static function config(): array
    {
        return self::$config ??= [
            'default_target' => self::string('malevich.default_target', 'default'),
            'render_directive' => self::string('malevich.render_directive', 'ui'),
            'cases_directive' => self::string('malevich.cases_directive', 'cases'),
            'has_directive' => self::string('malevich.has_directive', 'has'),
            'directives' => array_values(array_filter((array) config('malevich.directives', []), is_string(...))),
        ];
    }

    private static function string(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function outsideComponent(): never
    {
        throw new LogicException('Malevich: @'.self::renderDirective().' and the class map directives only work inside a Blade component, where $attributes is defined.');
    }
}
