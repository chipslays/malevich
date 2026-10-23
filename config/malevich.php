<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | An option is a prop that picks classes: <x-button variant="ghost">.
    | Every name listed here gets its own Blade directive that declares the
    | class map (@variant([...])) and a method on the attribute bag
    | ($attributes->variant('ghost')). Add your own: 'radius', 'shadow', ...
    |
    | Any other name works through @option('name', [...]).
    |
    */

    'options' => [
        'variant',
        'size',
        'color',
    ],

    /*
    |--------------------------------------------------------------------------
    | Directive Names
    |--------------------------------------------------------------------------
    |
    | The name of every directive Malevich registers, by what it does. Rename
    | any of them if it collides with a directive of your own or of another
    | package - Malevich checks the names on boot and tells you about a clash.
    | Run `php artisan view:clear` after changing them.
    |
    */

    'directives' => [
        'attributes' => 'attributes',
        'base' => 'base',
        'option' => 'option',
        'compound' => 'compound',
        'cases' => 'cases',
        'styled' => 'styled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | The directory where `php artisan make:malevich` writes new components
    | and `php artisan malevich:add` copies kit components.
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

    /*
    |--------------------------------------------------------------------------
    | Data Attributes
    |--------------------------------------------------------------------------
    |
    | Put the resolved value of every option on the component's main element:
    | <button data-variant="primary" data-size="lg">. Style children with
    | Tailwind's group-data-[variant=primary]: or select elements in tests.
    | A boolean option is written as data-loading when true, left out when false.
    |
    */

    'data_attributes' => false,

    /*
    |--------------------------------------------------------------------------
    | Class Merging
    |--------------------------------------------------------------------------
    |
    | 'auto'  - resolve Tailwind conflicts (px-4 vs px-8) with tailwind-merge
    |           when gehrisandro/tailwind-merge-laravel (or -php) is installed
    | false   - only remove duplicate classes
    |
    | Malevich::mergeClassesUsing() in a service provider always wins.
    |
    */

    'merge_classes' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Throw when an option gets a value its class maps don't define
    | (size="xl"), instead of silently rendering no classes. Meant for
    | development and tests. Malevich::strict(...) in a service provider
    | wins over this value.
    |
    */

    'strict' => env('MALEVICH_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Print an HTML comment in front of every element that explains its
    | classes: the component, each option's value and where it came from,
    | and which declaration added which classes. Never in production.
    | Malevich::debug(...) in a service provider wins over this value.
    |
    */

    'debug' => env('MALEVICH_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Component Catalog
    |--------------------------------------------------------------------------
    |
    | A page that renders every component with every value of its options:
    | http://your-app.test/malevich. Off unless the app runs locally; set
    | MALEVICH_CATALOG=true or false to decide yourself.
    |
    | head - HTML (Blade) for the page's <head>. When null, the page loads the
    |        same Vite entries as your app (the `input` of vite.config.js), so
    |        your theme, custom colors, fonts and JS apply to the previews.
    |        Set it when your CSS comes from somewhere else, e.g.
    |        '<link rel="stylesheet" href="/css/app.css">'.
    |
    */

    'catalog' => [
        'enabled' => env('MALEVICH_CATALOG', false),
        'path' => 'malevich',
        'middleware' => ['web'],
        'head' => null,
    ],

];
