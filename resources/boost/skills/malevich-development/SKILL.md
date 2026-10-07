---
name: malevich-development
description: Build and edit variant-driven Blade components with Malevich (@base, @variant, @color, @size, @directive, @compound, @preset, @ui). Activate when creating or changing Blade components in resources/views/components, building UI kit components (button, badge, alert, card, input, modal), adding variants/sizes/colors to a component, converting $attributes->class([...]) ternaries, using <x-malevich::primitive>, running make:malevich, or when a Blade file contains @ui or @variant.
---

# Malevich Development

Malevich (`malevich/malevich`) replaces `$attributes->class([... => $variant === 'x'])` ternaries with flat class maps. You declare which classes belong to which option value; `@ui` prints the final `class` and attributes on an element. Works with Tailwind or any CSS, no JS, no build step.

Use it for reusable Blade components with options (variant, color, size, flags). `@ui` works only inside component files, never in pages or layouts.

## How it works

1. Directives (`@base`, `@variant`, `@color`, `@size`, `@directive`, `@compound`, `@preset`) only **remember** what you declare.
2. `@ui` reads each option's value (from `@props`, then the tag attribute, then the preset), picks the classes, prints them.
3. **Directives MUST come before `@ui`**, right after `@props`.

Class order: `@base` -> class maps (file order) -> `@compound` -> `merge: ['class']` -> `class` from the tag. Duplicates removed.

## Minimal component

```blade
{{-- <x-badge color="green" class="ml-2">Paid</x-badge> --}}
@props(['color' => 'gray'])

@base('inline-flex rounded-full px-2 py-0.5 text-xs font-medium')

@color([
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-green-100 text-green-700',
])

<span @ui>{{ $slot }}</span>
```

Never write `$color` in the markup and never put `class="..."` on the `@ui` element: use `@base`.

## Cheat sheet

```blade
@props(['variant' => 'primary', 'size' => 'md', 'loading' => false, 'title' => null])

@base('inline-flex items-center')                         {{-- always-on classes --}}
@variant(['primary' => '...', 'outline' => '...'])        {{-- value -> classes --}}
@size(['sm' => '...', 'md' => '...'])
@directive('radius', ['md' => 'rounded-md'])              {{-- any option name --}}
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])       {{-- inner element "icon" --}}
@base('title', 'font-semibold')                           {{-- always-on for inner element --}}
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')   {{-- ALL conditions; list = any of --}}
@compound(['loading' => true], 'cursor-wait')             {{-- flag --}}
@preset('big', ['size' => 'lg'])                          {{-- used as preset="big" --}}

<button @ui(merge: ['type' => 'button'])>                 {{-- default attributes, user wins --}}
    <svg @ui('icon')></svg>
    <span @ui('title')>{{ $title }}</span>                {{-- picks up <x-slot:title class="..."> --}}
    {{ $slot }}
</button>
```

```bash
php artisan make:malevich button
php artisan vendor:publish --tag malevich:config
php artisan vendor:publish --tag malevich:components
php artisan view:clear     # after config changes or package upgrade
```

## Choosing the right tool

| Need | Use |
|---|---|
| Classes depend on one prop | `@variant` / `@color` / `@size` / `@directive('name', ...)` |
| Classes always present | `@base` |
| Classes depend on 2+ props or a flag | `@compound` |
| Named bundle of props | `@preset` (options must default to `null`) |
| Style an inner element | target: `@size('icon', [...])` + `@ui('icon')` |
| Let users style an inner element | named slot with the same name as the target |
| Default attributes / a11y | `@ui(merge: [...])` |
| Link/button/disabled behaviour for free | `<x-malevich::primitive :as="$as" @ui>` |
| Build on another component | `<x-other @ui />` |

## Essential rules

- Directives before `@ui`; always-on classes in `@base`, not `class=""`.
- Full literal class names only; never `'bg-'.$color.'-500'`.
- Every `@props` default must exist as a key in its map.
- Single quotes inside `@ui(...)` on `<x-...>` tags.
- With the primitive: pass `:as="$as"`; do not put `href`, `target`, `disabled`, `type` in `@props`.
- Match the project's existing component conventions, prefix and Tailwind version.

## References (read when relevant)

- [references/workflow.md](references/workflow.md): steps before writing, `make:malevich`, config, converting old components, tailwind-merge, verification checklist, troubleshooting.
- [references/directives.md](references/directives.md): all directives, class map rules (booleans, enums, `null`), `@compound`, `@preset`, where values come from, custom directives, shared partials.
- [references/ui-and-targets.md](references/ui-and-targets.md): all `@ui` forms, `merge:`, inner elements, named slots, building on other components, `$attributes` API.
- [references/primitive.md](references/primitive.md): `<x-malevich::primitive>` behaviour and building buttons/links on it.
- [references/rules.md](references/rules.md): hard rules, anti-patterns, design principles, project rules advice. Read before finishing any component.

## Templates

Finished, working components to start from (copy and adapt names/palette to the project): [templates/button.blade.php](templates/button.blade.php) (primitive, variants, sizes, spinner target), [templates/badge.blade.php](templates/badge.blade.php) (`@compound` matrix), [templates/alert.blade.php](templates/alert.blade.php) (targets, named slot, `merge:` a11y), [templates/card.blade.php](templates/card.blade.php) (presets, custom `@directive`), [templates/switch.blade.php](templates/switch.blade.php) (boolean option, inner element).
