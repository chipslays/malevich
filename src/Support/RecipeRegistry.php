<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\View\ComponentAttributeBag;
use Malevich\Recipe;
use WeakMap;

/**
 * Links a component's attribute bag to the Recipe declared in its template.
 *
 * Keyed by a WeakMap, so a recipe lives exactly as long as the component
 * instance that owns it: nothing leaks between renders, requests or
 * Octane workers, and there is nothing to clean up.
 */
final class RecipeRegistry
{
    /**
     * @var WeakMap<ComponentAttributeBag, Recipe>|null
     */
    private static ?WeakMap $recipes = null;

    public static function for(ComponentAttributeBag $attributes): Recipe
    {
        $recipes = self::$recipes ??= new WeakMap;

        return $recipes[$attributes] ??= new Recipe;
    }

    public static function flush(): void
    {
        self::$recipes = null;
    }
}
