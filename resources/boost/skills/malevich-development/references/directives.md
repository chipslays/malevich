# Directives reference

All directives must be declared **before** `@attributes`.

| Directive | Use |
|---|---|
| `@base('classes')` | Classes always present on the main element. |
| `@base('target', 'classes')` | Always-on classes for inner element `@attributes('target')`. Can be repeated, classes add up. |
| `@variant([...])`, `@color([...])`, `@size([...])` | Class map for `$variant` / `$color` / `$size`. Identical behaviour, only the name differs. |
| `@option('radius', [...])` | Class map for any option name. |
| `@option('radius', 'target', [...])` / `@variant('target', [...])` | Same, for an inner element. |
| `@cases('option', [value => [target => classes]])` | The same class maps as `@variant('target', [...])` per element, grouped by value. |
| `@compound([conds], 'classes')` | Classes when ALL conditions match. A list value means "any of". Added after all class maps. |
| `@compound('target', [conds], 'classes')` | Same for an inner element. |
| `@preset('name', [...])` | Named bundle of values, chosen with `preset="name"`. |
| `@defaults([...])` | Value of each option when nothing else picked one. Ranks below presets. |
| `@defaults('target', [...])` | Same, for one inner element; wins over the shared defaults there. |

## Class maps

Key = option value, item = classes. Classes can be a string, a list, or `@class`-style conditionals:

```blade
@color([
    'red' => 'bg-red-100 text-red-700',
    'green' => ['bg-green-100', 'text-green-700'],
    'blue' => ['bg-blue-100', 'animate-pulse' => $live],
])
```

- Unknown or `null` value: nothing is added, no error. With `Malevich::strict()` an unknown value throws (see below).
- Keys are case-sensitive.
- Booleans are looked up as `'true'` / `'false'`: `@option('checked', ['true' => '...', 'false' => '...'])`.
- Backed enums by value, plain enums by case name, numbers by string form (`:level="2"` -> `'2'`).
- `'*'` key = always. Works, but prefer `@base`.
- Order: classes are added in the order directives appear in the file. Duplicates removed.

## `@cases`

Use it when one prop changes the classes of several elements and you want to read "what does `teal` look like" in one place:

```blade
@cases('variant', [
    'teal' => ['default' => 'bg-teal-600', 'title' => 'text-teal-100', 'glow' => 'absolute blur-3xl'],
    'dark' => ['default' => 'bg-gray-900', 'title' => 'text-white'],
])
```

- First argument: the option name (`variant`, `size`, any `@option` name).
- Inner keys are targets, `default` is the main element. A plain string or list as a value styles the main element.
- It fills the same slots as one `@variant('target', [...])` per element; several `@cases` calls add up. A later `@variant([...])` for the same element and option **replaces** the theme's classes.
- Conditional classes (`['a', 'b' => $cond]`) go under a target key, never directly as a value.
- An optional part: no `@base` for it, all classes in the variants, render it inside `@has('target') ... @endhas`.

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
@preset('featured', ['variant' => 'elevated', 'padding' => 'lg'])
@preset('danger', [
    'default' => ['color' => 'red'],
    'icon' => ['color' => 'red', 'size' => 'lg'],
])

@defaults(['variant' => 'flat', 'padding' => 'md'])
```

- Chosen by the `preset` attribute (or prop).
- A preset only provides **fallback** values; explicit values win (`<x-card preset="featured" padding="sm">`).
- A preset wins over `@defaults`.
- A prop with a non-null `@props` default counts as explicit and beats the preset. Put defaults in `@defaults`; if an option must be in `@props` (markup uses `$variant`), give it a `null` default.
- Presets can be keyed by target (`default` = main element).

## `@defaults`

```blade
@defaults(['variant' => 'primary', 'size' => 'md'])   {{-- every element --}}
@defaults('icon', ['size' => 'sm'])                   {{-- inner element "icon" only --}}
```

- The CVA `defaultVariants` equivalent: the last fallback, used only when the tag, `@props` and the preset give nothing.
- Applies to every element like a tag attribute; a target form or a per-target array (`['size' => ['default' => 'md', 'icon' => 'sm']]`) overrides one element.
- Takes part in `@compound` conditions. Later `@defaults` calls replace earlier values.
- Does not create `$variant` in the template; add `'variant' => null` to `@props` if the markup needs it.

## Where values come from (first non-null wins)

1. Explicit call: `$attributes->size('sm')`.
2. Template variable with the same name (normally `@props`).
3. Attribute with the same name on the component tag (if not in `@props`).
4. Active preset.
5. `@defaults`.

Per-target values: pass an array keyed by target, main element is `default`:
`<x-button :size="['default' => 'lg', 'spinner' => 'sm']" />`.

## Directive names and collisions

- `@attributes`, `@cases`, `@has` are renamable: `render_directive`, `cases_directive`, `has_directive` in `config/malevich.php`. `@base`, `@compound`, `@preset`, `@defaults`, `@option` are fixed.
- Boot throws if a configured name is a Blade built-in, is already registered by the app or another package, is a Malevich name, is a method of `$attributes`, or is invalid.
- A directive written without parentheses is left as text, so Tailwind's `@variant dark` inside a template stay intact.

## Strict mode

`Malevich::strict(! app()->isProduction());` in `AppServiceProvider::boot()` (like `Model::shouldBeStrict()`). An option value that is not a key of its class map then throws `Malevich\Exceptions\UnknownValueException`: `Malevich: size [xl] is not defined; expected one of: sm, md, lg.` Checked whatever the source (tag, `@props`, preset, `@defaults`). Not an error: `null`, `'*'`, an element without a map for that option (or only a `'*'` key), a value that is a key on another element's map or tested by a `@compound`, and `false` on a `['true' => ...]` map. Reset by `Malevich::flush()`.

## Custom option directives

```php
// config/malevich.php
'options' => ['variant', 'size', 'color', 'radius'],
```

Gives `@radius([...])` and `$attributes->radius()`; `make:malevich` will scaffold it too. Run `php artisan view:clear` afterwards. Boot throws if the name is a built-in Blade directive (`if`, `class`, `props`, ...), a Malevich directive (`base`, `compound`, `preset`, `defaults`, `option`, the render directive), a method of `$attributes` (`merge`, `get`, `only`, ...), or contains anything but letters, digits, `_`. For one or two components use `@option('name', [...])` instead of registering.

## Sharing class maps

Move maps into a partial and include after `@props`:

```blade
{{-- components/ui/partials/sizes.blade.php --}}
@size(['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4', 'lg' => 'h-12 px-6 text-lg'])
```

```blade
@props(['size' => 'md'])
@include('components.ui.partials.sizes')
<button @attributes>{{ $slot }}</button>
```
