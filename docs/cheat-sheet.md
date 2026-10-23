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

## Using it

```blade
<x-button>Save</x-button>
<x-button variant="outline" size="sm" class="mt-2" wire:click="save">Save</x-button>
<x-button :size="['default' => 'lg', 'icon' => 'sm']">Big button, small icon</x-button>
<x-button loading>Saving</x-button>
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
