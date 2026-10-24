# Cheat sheet

## In a component

```blade
{{-- defaults --}}
@props(['variant' => 'primary', 'size' => 'md', 'loading' => false, 'title' => null])

{{-- always-on classes --}}
@base('inline-flex items-center')

{{-- value -> classes --}}
@variant(['primary' => '...', 'outline' => '...'])
@size(['sm' => '...', 'md' => '...'])
@color(['gray' => '...', 'red' => '...'])

{{-- any option name --}}
@option('radius', ['md' => 'rounded-md', 'full' => 'rounded-full'])

{{-- booleans --}}
@option('checked', ['true' => '...', 'false' => '...'])

{{-- inner element "icon" / "title" --}}
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])
@base('title', 'font-semibold')

{{-- grouped by value: one entry per value, classes per element --}}
@cases('variant', [
    'primary' => ['default' => '...', 'icon' => '...'],
    'outline' => ['default' => '...', 'icon' => '...'],
])

{{-- when ALL conditions match; a list means "any of" --}}
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')
@compound(['variant' => ['outline', 'ghost']], 'bg-transparent')
@compound(['loading' => true], 'cursor-wait')
@compound(['size' => 'sm'], targets: ['default' => 'px-2', 'icon' => 'size-3'])
@compound(['size' => 'sm'], targets: ['leading,trailing' => 'size-3'])   {{-- one key, several elements --}}
{{-- @attributes(override: ['aria-busy' => 'true']): wins over the tag; merge: is only a default --}}

<button class="relative" @attributes(merge: ['type' => 'button'])>   {{-- class="" works like @base --}}
    <svg @attributes('icon')></svg>
    <span @attributes('title')>{{ $title }}</span>                  {{-- takes <x-slot:title class="..."> --}}
    @styled('badge')<i @attributes('badge')></i>@endstyled         {{-- only when it has classes --}}
    {{ $slot }}
</button>
```

## More `@attributes` forms

```blade
<button @attributes>                                         {{-- main element --}}
<button @attributes(merge: ['type' => 'button'])>            {{-- defaults; the tag wins --}}
<button @attributes(override: ['aria-busy' => $loading ? 'true' : null])>   {{-- null: leave it, false: remove --}}
<button @attributes(merge: ['type' => 'button'], override: ['role' => 'switch'])>
<div @attributes(class: $classes)>                           {{-- classes as an argument --}}
<h2 @attributes('title')>                                    {{-- inner element + <x-slot:title> attributes --}}
<h2 @attributes('heading', slot: $title)>                    {{-- attributes of a slot with another name --}}
<h2 @attributes('title', slot: false)>                       {{-- ignore the slot's attributes --}}
<x-icon @attributes('icon') />                               {{-- works on component tags too --}}
```

## Using it

```blade
<x-button>Save</x-button>
<x-button variant="outline" size="sm" class="mt-2" wire:click="save">Save</x-button>
<x-button :size="['default' => 'lg', 'icon' => 'sm']">Big button, small icon</x-button>
<x-button loading>Saving</x-button>
<x-button aria-busy="false" loading>Go</x-button>        {{-- override: aria-busy="true" wins --}}
```

### Inner elements from the outside

```blade
{{-- targeted attributes: target:option --}}
<x-button icon:size="lg" icon:class="text-red-500">Save</x-button>

{{-- settings on a named slot --}}
<x-card>
    <x-slot:title size="lg" class="font-bold" id="billing-title">Billing</x-slot:title>
    <x-slot:title :size="$titleSize">Billing</x-slot:title>
</x-card>

{{-- modifiers: target:prop.modifier[arg] --}}
<x-caption text:value.uppercase.limit[12]="Account settings" />
<x-caption text:value.uppercase[false].limit[]="Account settings" />
<x-caption :text:value.limit[24]="$caption" />
```

## The `$attributes` API

```blade
<div {{ $attributes->variant($variant)->size($size) }}>
<svg {{ $attributes->for('icon')->color('gray')->merge(['aria-hidden' => 'true']) }}>
<div {{ $attributes->use(['size' => 'lg', 'color' => 'red']) }}>
<div {{ $attributes->use('icon', ['size' => 'lg']) }}>       {{-- inner element + values --}}
<h2 {{ $attributes->for('title')->slot($title) }}>           {{-- attach a named slot --}}
<div {{ $attributes->override(['role' => 'switch']) }}>
<div x-bind:class="'{{ $attributes->for('panel')->toClasses() }}'">

{{-- every call returns a new object: branch safely --}}
@php($button = $attributes->size($size))
<div {{ $button }}><span {{ $button->for('icon') }}></span></div>

{{-- merge() returns a plain bag: call it last --}}
{{ $attributes->variant($variant)->merge([...]) }}
```

```php
// modifiers inside the component
$settings = $attributes->for('text')->modifiers('value');   // text:value.uppercase.limit[24]
```

## In the browser (Alpine)

```php
// config/malevich.php
'data_attributes' => true,   // data-component="button" data-variant="danger" data-loading
```

```blade
{{-- Tailwind data-* variants: switch only the attribute --}}
@base('data-[tone=danger]:bg-red-600 data-[tone=info]:bg-blue-600')
<div x-data="{ tone: 'info' }" x-bind:data-tone="tone" @attributes>...</div>

{{-- let the server prepare every variant --}}
<button
    x-data="{ variant: 'primary', classes: {{ Js::from($attributes->size($size)->classesFor('variant')) }} }"
    x-bind:class="classes[variant]"
    x-on:click="variant = 'danger'"
>{{ $slot }}</button>

{{ Js::from($attributes->classesFor('variant', ['primary', 'danger'])) }}   {{-- only some values --}}
{{ Js::from($attributes->for('icon')->classesFor('size')) }}                {{-- inner element --}}
```

## Configuration

```php
// config/malevich.php
'options' => ['variant', 'size', 'color', 'radius'],     // adds @radius and ->radius('full')
'directives' => ['styled' => 'present'],                 // rename on a clash: @present ... @endpresent
'components' => ['path' => resource_path('views/components'), 'prefix' => 'ui'],   // <x-ui::button>
'merge_classes' => false,                                // only remove duplicates
'playground' => ['enabled' => true, 'path' => 'malevich', 'middleware' => ['web', 'auth']],
```

```php
// AppServiceProvider::boot(): a call in code wins over the config
use Malevich\Malevich;

Malevich::strict(! app()->isProduction());
Malevich::debug(app()->isLocal());
Malevich::playground(app()->isLocal());
Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes));
```

## Commands

```bash
php artisan malevich:install                          # config + editor autocomplete
php artisan make:malevich button                      # new component
php artisan malevich:add button                       # copy the ready-made kit button
php artisan malevich:lint                             # find unknown values in all views
php artisan vendor:publish --tag malevich:config      # publish the config
php artisan vendor:publish --tag malevich:components  # publish the primitive
php artisan vendor:publish --tag malevich:theme       # theme tokens for kit components
php artisan view:clear                                # after config changes or upgrades
```

## Tools

| | |
|---|---|
| `/malevich` | the [component playground](09-playground.md) (local only) |
| `MALEVICH_DEBUG=true` | an HTML comment explaining every element |
| `MALEVICH_STRICT=true` | an exception on unknown values |
| `->assertSeeClasses('a b')` | test classes in any order |
| `expect($html)->toHaveClasses('a b')` | the same in Pest |
