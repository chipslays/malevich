# `@attributes` in detail

`@attributes` prints the attributes of one element: the final `class` plus everything else that belongs there. You put it inside an HTML tag, like Blade's own `@class`:

```blade
<button @attributes>{{ $slot }}</button>
```

Think of it this way: **`$attributes` is what the component received** (`variant="ghost"`, `class="mt-2"`, `wire:click`, ...). **`@attributes` is what the element gets** - options turned into classes, option names removed, everything else passed through.

## All forms

| You write | What you get |
|---|---|
| `@attributes` | The main element: classes + every attribute from the component tag. |
| `@attributes('icon')` | The inner element `icon`: only its classes. See [Inner elements](05-inner-elements.md). |
| `@attributes(merge: ['type' => 'button'])` | The main element + default attributes. |
| `@attributes(override: ['aria-busy' => 'true'])` | The main element + attributes that win over the tag's. |
| `@attributes('title')` | The inner element `title` + the attributes of `<x-slot:title>`, if there is one. |
| `@attributes('heading', slot: $title)` | The inner element + the attributes of a slot with another name. |
| `@attributes('title', slot: false)` | The inner element, ignoring the slot's attributes. |

## Default attributes: `merge:`

`merge:` gives the element attributes it should have **unless the user says otherwise**:

```blade
<button @attributes(merge: ['type' => 'button'])>{{ $slot }}</button>
```

```blade
<x-button>Go</x-button>                 {{-- <button class="..." type="button"> --}}
<x-button type="submit">Go</x-button>   {{-- <button class="..." type="submit">: the user wins --}}
```

- Attributes from the tag **replace** `merge:` attributes with the same name.
- A `class` in `merge:` is **added**, not replaced: `merge: ['class' => 'select-none']`.
- Values can be dynamic: `merge: ['aria-checked' => $checked ? 'true' : 'false']`.

It's the same idea as `$attributes->merge([...])` in plain Blade.

## Forced attributes: `override:`

Sometimes the component must have the last word: a loading button is busy, whatever `aria-busy` the user passed. `override:` wins over the tag:

```blade
<button @attributes(override: ['aria-busy' => $loading ? 'true' : null])>{{ $slot }}</button>
```

```blade
<x-button aria-busy="false">Go</x-button>          {{-- aria-busy="false": null leaves it alone --}}
<x-button loading aria-busy="false">Go</x-button>  {{-- aria-busy="true": override wins --}}
```

- `null` leaves the attribute as the user set it (or absent); `false` removes it.
- Values are escaped, like `merge:`.
- Classes can't be overridden: they always add up. `override: ['class' => ...]` throws; use a class map, or `class` in `merge:` to add one.
- Both can be used together: `@attributes(merge: ['type' => 'button'], override: ['role' => 'switch'])`.

## What ends up in the HTML

On the **main element**:

1. `class`, built in this order:
   1. `@base`
   2. the element's own `class="..."`
   3. class maps, in the order they are written
   4. matching `@compound` classes
   5. `class` from `merge:`
   6. `class` passed to the component (`<x-button class="mt-2">`)

   Duplicates are removed. Without any classes there's no empty `class=""`.
2. Every other attribute from the tag: `id`, `wire:click`, `x-data`, `data-*`, `aria-*`, ...
3. `merge:` attributes the tag didn't set.
4. `override:` attributes, replacing any of the above with the same name.

Option names (`variant`, `size`, any `@option` name) are **not** printed - Malevich used them to pick classes.

On an **inner element** (`@attributes('icon')`): only its own classes, its `merge:` attributes and the attributes of its [slot](05-inner-elements.md#named-slots), plus [targeted attributes](05-inner-elements.md#internal-elements-configure-them-from-the-component-tag) such as `icon:class="..."` from the component tag. Plain attributes from the component tag always go to the main element.

## Rules

- `@attributes` works only **inside a component file** - it needs the component's `$attributes`.
- **Declare directives before `@attributes`.** A directive written after it isn't known yet.
- You can use `@attributes` as many times as you like, also for the same element.
- It works on component tags too: `<x-icon @attributes('icon') />`. See [Building components](06-building-components.md).
- Inside `<x-...>` tags use **single quotes** in the arguments: `@attributes(merge: ['type' => 'submit'])`. A double quote would end the attribute.
- A `class="..."` next to `@attributes` on a plain tag may contain text and `{{ }}`. For anything else (`{!! !!}`, `@if` inside the class) pass the classes as an argument: `@attributes(class: $classes)`.

> [!NOTE]
> The name `@attributes` can be changed in the [config](13-configuration.md#renaming-directives).

---

Next: [Inner elements](05-inner-elements.md)
