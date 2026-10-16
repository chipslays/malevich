---
name: malevich-development
description: Build and edit variant-driven Blade components with Malevich (@base, @variant, @color, @size, @option, @cases, @compound, @attributes). Activate when creating or changing Blade components in resources/views/components, building UI kit components (button, badge, alert, card, input, modal), adding variants/sizes/colors to a component, converting $attributes->class([...]) ternaries, using <x-malevich::primitive>, running make:malevich, or when a Blade file contains @attributes or @variant.
---

# Malevich Development

Malevich (`malevich/malevich`) replaces `$attributes->class([... => $variant === 'x'])` ternaries with flat class maps. You declare which classes belong to which option value; `@attributes` prints the final `class` and attributes on an element. Works with Tailwind or any CSS, no JS, no build step.

Use it for reusable Blade components with options (variant, color, size, flags). `@attributes` works only inside component files, never in pages or layouts.

## How it works

1. Directives (`@base`, `@variant`, `@color`, `@size`, `@option`, `@cases`, `@compound`) only **remember** what you declare.
2. `@attributes` reads each option's value (from `@props` with its default, or the tag attribute), picks the classes, prints them.
3. **Directives MUST come before `@attributes`**, right after `@props`.

Class order: `@base` -> the element's own `class=""` -> class maps (file order) -> `@compound` -> `merge: ['class']` -> `class` passed to the component. Duplicates removed.

## Minimal component

```blade
{{-- <x-badge color="green" class="ml-2">Paid</x-badge> --}}
@props(['color' => 'gray'])

@base('inline-flex rounded-full px-2 py-0.5 text-xs font-medium')

@color([
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-green-100 text-green-700',
])

<span @attributes>{{ $slot }}</span>
```

Never write `$color` in the markup: `@attributes` reads the option from `@props` (its default) or the tag. Always-on classes go in `@base` or in a `class="..."` on the `@attributes` element: inside a component both mean the same (the option classes go on top). Follow the project's convention.

## Cheat sheet

```blade
@props(['variant' => 'primary', 'size' => 'md', 'loading' => false])   {{-- defaults --}}

@base('inline-flex items-center')                         {{-- always-on classes --}}
@variant(['primary' => '...', 'outline' => '...'])        {{-- value -> classes --}}
@size(['sm' => '...', 'md' => '...'])
@option('radius', ['md' => 'rounded-md'])              {{-- any option name --}}
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])       {{-- inner element "icon" --}}
@base('title', 'font-semibold')                           {{-- always-on for inner element --}}
@cases('variant', ['teal' => ['default' => '...', 'title' => '...'], 'dark' => [...]])   {{-- same maps, grouped by value --}}
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')   {{-- ALL conditions; list = any of --}}
@compound(['loading' => true], 'cursor-wait')             {{-- flag --}}
@compound(['size' => 'sm'], targets: ['default' => 'px-2', 'icon' => 'size-3'])   {{-- one combination, several elements --}}

<button @attributes(merge: ['type' => 'button'])>                 {{-- default attributes, user wins --}}
    <svg @attributes('icon')></svg>
    <span @attributes('title')>{{ $title }}</span>                {{-- picks up <x-slot:title class="..."> --}}
    @has('badge')<i @attributes('badge')></i>@endhas             {{-- only when that element has classes --}}
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
| Classes depend on one prop | `@variant` / `@color` / `@size` / `@option('name', ...)` |
| Classes always present | `@base` |
| Several elements change together per value of one prop | `@cases('variant', [value => [target => classes]])` |
| An element exists only in some variants | `@has('target') ... @endhas`, all its classes in the variants (no `@base`) |
| Classes depend on 2+ props or a flag | `@compound` |
| Default value of an option | its `@props` default |
| Catch value typos in dev | `MALEVICH_STRICT=true` or `Malevich::strict(! app()->isProduction())` |
| See why an element has its classes | `MALEVICH_DEBUG=true` or `Malevich::debug()`: an HTML comment in front of every element |
| Set up a project (config, Tailwind IntelliSense) | `php artisan malevich:install` |
| Children react to the parent's option | `'data_attributes' => true` + `group-data-[variant=x]:` |
| Switch an option in the browser (Alpine) | `data-*` variants in the maps + `x-bind:data-x`, or `{{ Js::from($attributes->classesFor('variant')) }}` + `x-bind:class="classes[variant]"` |
| Test a component's classes | `->assertSeeClasses('a b')` / `expect($html)->toHaveClasses(...)` |
| Find unknown option values in all views (CI) | `php artisan malevich:lint` (`--format=github`) |
| Style an inner element | target: `@size('icon', [...])` + `@attributes('icon')` |
| Let users style an inner element | named slot with the same name as the target |
| Default attributes / a11y | `@attributes(merge: [...])` |
| Link/button/disabled behaviour for free | `<x-malevich::primitive :as="$as" @attributes>` |
| Build on another component | `<x-other @attributes />` |

## Essential rules

- Directives before `@attributes`; always-on classes in `@base` or the element's own `class=""`.
- Full literal class names only; never `'bg-'.$color.'-500'`.
- Every `@props` default of an option must exist as a key in its map.
- With the primitive: pass `:as="$as"`; do not put `href`, `target`, `disabled`, `type` in `@props`.
- Match the project's existing component conventions, prefix and Tailwind version.

## References (read when relevant)

- [references/workflow.md](references/workflow.md): steps before writing, `make:malevich`, config, converting old components, tailwind-merge, verification checklist, troubleshooting.
- [references/directives.md](references/directives.md): all directives, class map rules (booleans, enums, `null`), `@compound`, where values come from, custom options, shared partials.
- [references/ui-and-targets.md](references/ui-and-targets.md): all `@attributes` forms, `merge:`, inner elements, named slots, building on other components, `$attributes` API.
- [references/primitive.md](references/primitive.md): `<x-malevich::primitive>` behaviour and building buttons/links on it.
- [references/rules.md](references/rules.md): hard rules, anti-patterns, design principles, project rules advice. Read before finishing any component.
