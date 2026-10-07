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
    | Components
    |--------------------------------------------------------------------------
    |
    | The directory where `php artisan make:malevich` writes new components.
    | If it exists, Malevich registers it with Blade as anonymous components.
    | Subfolders become dots: forms/input.blade.php is <x-forms.input>.
    |
    | The prefix only changes the tag, the files stay where they are:
    |
    |   'prefix' => null   ->  button.blade.php is <x-button>
    |   'prefix' => 'ui'   ->  button.blade.php is <x-ui::button>
    |
    */

    'components' => [
        'path' => resource_path('views/components'),
        'prefix' => null,
    ],

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

];
