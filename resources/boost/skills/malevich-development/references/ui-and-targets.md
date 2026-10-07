# `@ui`, targets and slots

## Forms

| Form | Effect |
|---|---|
| `@ui` | Main element: classes + all attributes from the component tag. |
| `@ui('icon')` | Inner element: only its own classes. Tag attributes never go to inner elements. |
| `@ui(merge: ['type' => 'button'])` | Default attributes; the user's attributes with the same name win. |
| `@ui('title')` | Also picks up attributes of a named slot `<x-slot:title class="...">` with the same name. |
| `@ui('heading', slot: $title)` | Use a differently named slot. |
| `@ui('title', slot: false)` | Ignore the slot's attributes. |
| `@ui('title', merge: ['id' => 'x'])` | Inner element + default attributes. |

Arguments: target (1st, positional), `slot:`, `merge:` (named).

## Output rules (main element)

- `class` order: `@base` -> class maps (file order) -> `@compound` -> `class` from `merge:` -> `class` from the tag. Duplicates removed. No empty `class=""`.
- All other tag attributes pass through (`id`, `wire:*`, `x-*`, `data-*`, `aria-*`).
- `merge:` attributes appear unless the tag sets them. `class` in `merge:` is added, not replaced. Values may be dynamic: `merge: ['aria-checked' => $checked ? 'true' : 'false']`.
- Option names (`variant`, `color`, `size`, custom) and `preset` are never printed. If `variant="..."` shows up in HTML, the directive is missing or declared after `@ui`.
- `@ui` may be used many times in one component, also for the same target.
- Only valid inside a component file (needs `$attributes`).
- On `<x-...>` tags use **single quotes** inside arguments.
- `@ui` works on components too: `<x-heroicon-o-check @ui('icon') />`. The child must print `$attributes`.
- The name can be changed with config `render_directive`.

## Targets (inner elements)

Declare per-target maps with a target name as the first argument, render with `@ui('target')`:

```blade
@base('spinner', 'animate-spin')
@size('spinner', ['sm' => 'size-3', 'md' => 'size-4'])

<button @ui>
    <svg @ui('spinner')>...</svg>
</button>
```

- One `size="sm"` styles main element and spinner, each with its own classes.
- Any directive accepts a target: `@color('icon', ...)`, `@base('title', ...)`, `@compound('icon', ...)`, presets keyed by target.
- Targets get only their own classes. `class` and other tag attributes go to the main element.
- To let users style an inner element, use a named slot with the same name.

## Named slots

```blade
@props(['title' => null])
@base('title', 'text-lg font-semibold')

<div @ui>
    @if ($title)<h3 @ui('title')>{{ $title }}</h3>@endif
    {{ $slot }}
</div>
```

```blade
<x-card><x-slot:title class="text-2xl" id="billing">Billing</x-slot:title></x-card>
{{-- <h3 class="text-lg font-semibold text-2xl" id="billing"> --}}
```

- Reacts only to a **named slot**; a string prop (`title="Billing"`) has no attributes, nothing happens.
- Slot `class` is added after the element's own classes.

## Building on other components

`@ui` on a component tag hands classes and attributes to it as `$attributes`. This is how components stack, including on the primitive. The child must print `$attributes`.

## `$attributes` API (rarely needed)

Same engine as methods. They **cannot see template variables**, pass values yourself:

```blade
<div {{ $attributes->variant($variant)->size($size) }}>
<svg {{ $attributes->for('icon')->color('gray')->merge(['aria-hidden' => 'true']) }}>
<div x-bind:class="'{{ $attributes->for('panel')->toClasses() }}'">
```

`->variant()/->color()/->size()/custom()`, `->directive('name', 'x')`, `->use([...])`, `->use('target', [...])`, `->for('target')`, `->preset('name')`, `->slot($slot)`, `->merge([...])`, `->toClasses()`, plus regular `$attributes` methods. Each call returns a new object. Explicit values win over `@props`. Call option methods **before** `->merge()` (merge returns a bag that doesn't know your class maps).
