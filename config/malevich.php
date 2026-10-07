<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Axes
    |--------------------------------------------------------------------------
    |
    | Every name listed here becomes a Blade directive that declares a class
    | map (@variant([...])) and a fluent method on the attribute bag
    | ($attributes->variant('solid')). Add your own: 'radius', 'shadow', ...
    |
    | Any other name still works through @directive('name', [...]).
    |
    */

    'directives' => [
        'variant',
        'size',
        'color',
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Directive
    |--------------------------------------------------------------------------
    |
    | Name of the directive that prints an element's resolved attributes:
    | <button @ui> ... </button>, <svg @ui('icon')>. Change it if 'ui'
    | collides with a directive of your own.
    |
    */

    'render_directive' => 'ui',

    /*
    |--------------------------------------------------------------------------
    | Default Target
    |--------------------------------------------------------------------------
    |
    | Target name of the component's root element. Only change it if you
    | really need a named target called 'default'.
    |
    */

    'default_target' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | Where `php artisan make:malevich` writes new components. If the
    | directory exists, Malevich registers it with Blade:
    |
    |   'prefix' => null   ->  button.blade.php is <x-button>
    |   'prefix' => 'ui'   ->  button.blade.php is <x-ui::button>
    |
    */

    'components' => [
        'path' => resource_path('views/components/ui'),
        'prefix' => null,
    ],

];
