<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registered Directives
    |--------------------------------------------------------------------------
    |
    | Directives listed here are automatically registered as both a Blade
    | directive and a ComponentAttributeBag macro. For example, adding
    | 'color' makes the @color([...]) directive and the
    | $attributes->color('...') method available in your components.
    |
    | Add your own directive names here to extend the default
    | @variant / @size / @color set with as many custom directives as
    | your design system needs (e.g. 'radius', 'shadow', 'spacing').
    |
    */
    'directives' => [
        'variant',
        'size',
        'color',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Target Name
    |--------------------------------------------------------------------------
    |
    | The target name used whenever ->for() is not called explicitly. All
    | directive values registered without an explicit target (or resolved
    | via $attributes->variant()/->color()/etc. directly) are stored and
    | resolved under this name.
    |
    | Changing this only matters if 'default' collides with a target name
    | you actually use in a multi-element component (e.g. via ->for()).
    | In most projects the default value is fine as-is.
    |
    */
    'default_target' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Component Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the anonymous Blade components shipped with (and
    | published by) this package, such as <x-ui::primitive>.
    |
    */
    'components' => [

        /*
        | Filesystem path Blade will scan for anonymous components.
        | This is where components you publish via
        | `php artisan vendor:publish --tag malevich:components` end up.
        */
        'path' => resource_path('views/components/ui'),

        /*
        | Tag prefix used to register the components above.
        | For example, a component file named 'primitive.blade.php' inside
        | the configured path becomes available as <x-ui::primitive>.
        */
        'prefix' => 'ui',

    ],

];
