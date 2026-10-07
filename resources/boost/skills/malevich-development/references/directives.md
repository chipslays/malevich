# Directives reference

All directives must be declared **before** `@ui`.

| Directive | Use |
|---|---|
| `@base('classes')` | Classes always present on the main element. |
| `@base('target', 'classes')` | Always-on classes for inner element `@ui('target')`. Can be repeated, classes add up. |
| `@variant([...])`, `@color([...])`, `@size([...])` | Class map for `$variant` / `$color` / `$size`. Identical behaviour, only the name differs. |
| `@directive('radius', [...])` | Class map for any option name. |
| `@directive('radius', 'target', [...])` / `@variant('target', [...])` | Same, for an inner element. |
| `@compound([conds], 'classes')` | Classes when ALL conditions match. A list value means "any of". Added after all class maps. |
| `@compound('target', [conds], 'classes')` | Same for an inner element. |
| `@preset('name', [...])` | Named bundle of values, chosen with `preset="name"`. |

## Class maps

Key = option value, item = classes. Classes can be a string, a list, or `@class`-style conditionals:

```blade
@color([
    'red' => 'bg-red-100 text-red-700',
    'green' => ['bg-green-100', 'text-green-700'],
    'blue' => ['bg-blue-100', 'animate-pulse' => $live],
])
```

- Unknown or `null` value: nothing is added, no error.
- Keys are case-sensitive.
- Booleans are looked up as `'true'` / `'false'`: `@directive('checked', ['true' => '...', 'false' => '...'])`.
- Backed enums by value, plain enums by case name, numbers by string form (`:level="2"` -> `'2'`).
- `'*'` key = always. Works, but prefer `@base`.
- Order: classes are added in the order directives appear in the file. Duplicates removed.

## `@compound`

```blade
@compound(['variant' => 'soft', 'color' => 'red'], 'bg-red-100')
@compound(['variant' => ['outline', 'ghost'], 'color' => 'red'], 'hover:bg-red-50')
@compound(['loading' => true], 'cursor-wait opacity-75')
@compound('icon', ['size' => 'sm'], 'mr-1')
```

- Use when a class depends on 2+ options, or on a flag with no class map of its own (`loading`, `active`, `invalid`).
- Works with props that have no class map.
- If the class depends on one option only, use that option's map instead.

## `@preset`

```blade
@props(['preset' => null, 'variant' => null, 'padding' => null])

@preset('featured', ['variant' => 'elevated', 'padding' => 'lg'])
@preset('danger', [
    'default' => ['color' => 'red'],
    'icon' => ['color' => 'red', 'size' => 'lg'],
])
```

- Chosen by the `preset` prop/attribute.
- A preset only provides **fallback** values; explicit values win (`<x-card preset="featured" padding="sm">`).
- A prop with a non-null default counts as explicit, so options in preset components must default to `null`.
- Presets can be keyed by target (`default` = main element).

## Where values come from (first non-null wins)

1. Template variable with the same name (normally `@props`, which also provides the default).
2. Attribute with the same name on the component tag (if not in `@props`).
3. Active preset.

Per-target values: pass an array keyed by target, main element is `default`:
`<x-button :size="['default' => 'lg', 'spinner' => 'sm']" />`.

## Custom option directives

```php
// config/malevich.php
'directives' => ['variant', 'size', 'color', 'radius'],
```

Gives `@radius([...])` and `$attributes->radius()`; `make:malevich` will scaffold it too. Run `php artisan view:clear` afterwards. Boot throws if the name is a built-in Blade directive (`if`, `class`, `props`, ...), a Malevich directive (`base`, `compound`, `preset`, `directive`, the render directive), a method of `$attributes` (`merge`, `get`, `only`, ...), or contains anything but letters, digits, `_`. For one or two components use `@directive('name', [...])` instead of registering.

## Sharing class maps

Move maps into a partial and include after `@props`:

```blade
{{-- components/ui/partials/sizes.blade.php --}}
@size(['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4', 'lg' => 'h-12 px-6 text-lg'])
```

```blade
@props(['size' => 'md'])
@include('components.ui.partials.sizes')
<button @ui>{{ $slot }}</button>
```
