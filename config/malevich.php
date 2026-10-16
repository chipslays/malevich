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
        'has' => 'has',
    ],

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

];
