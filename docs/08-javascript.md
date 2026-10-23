# JavaScript and CSS hooks

Options are resolved on the server. This chapter shows how CSS, tests and JavaScript can still see them, and how to switch a variant in the browser without a request.

## Data attributes

Turn on `data_attributes` in the [config](13-configuration.md):

```php
// config/malevich.php
'data_attributes' => true,
```

Now the main element says which component it is and which options it got:

```blade
<x-button variant="danger" size="lg" loading>Save</x-button>
```

```html
<button class="..." data-component="button" data-variant="danger" data-size="lg" data-loading>Save</button>
```

What it's good for:

- **Children react to the parent without props.** With `group` on the button: `<svg class="group-data-[variant=danger]:text-white">`. Or plain CSS: `[data-variant=danger] .icon { ... }`.
- **Stable selectors for tests and Dusk:** `button[data-variant=danger]`, `[data-component=button]`.

The details:

- `data-component` is the tag name without `x-`: `button`, `forms.input`, `ui::badge`.
- Only options with a class map are written, and only on the main element.
- A boolean is written as `data-loading` when true and left out when false, so Tailwind's `data-loading:` variant matches exactly when the flag is on.
- An attribute you pass yourself (`data-variant="custom"`) wins.

## Switching a variant in the browser

A tab becomes active, an alert changes its tone - no round-trip needed. There are two ways, and neither needs a JavaScript package.

### 1. Data attributes in the class maps

Write the classes of every state with Tailwind's `data-*` variants, and switch only the attribute:

```blade
@base('rounded-lg px-4 data-[tone=danger]:bg-red-600 data-[tone=info]:bg-blue-600')

<div x-data="{ tone: 'info' }" x-bind:data-tone="tone" @attributes>...</div>
```

### 2. `classesFor()` - let the server prepare every variant

Malevich resolves the full class string of every value and hands the list to Alpine:

```blade
@props(['size' => 'md'])

@variant([
    'primary' => 'bg-zinc-900 text-white',
    'danger' => 'bg-red-600 text-white',
])

@size([
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6',
])

@compound(['variant' => 'danger', 'size' => 'lg'], 'shadow-lg')

<button
    x-data="{ variant: 'primary', classes: {{ Js::from($attributes->size($size)->classesFor('variant')) }} }"
    x-bind:class="classes[variant]"
    x-on:click="variant = 'danger'"
>{{ $slot }}</button>
```

For `<x-button size="lg">` Alpine gets:

```js
{ primary: 'bg-zinc-900 text-white h-12 px-6', danger: 'bg-red-600 text-white h-12 px-6 shadow-lg' }
```

- Each value is resolved exactly like `@attributes` would: `@base`, the other options, `@compound`, `class` from the tag, tailwind-merge.
- All classes are written out in your Blade file, so Tailwind finds them.
- `classesFor('variant')` uses every value the component knows; `classesFor('variant', ['primary', 'danger'])` only those.
- For an inner element: `$attributes->for('icon')->classesFor('size')`.
- Pass the other options yourself (`->size($size)`): the [`$attributes` API](14-attributes-api.md) can't see your template variables.

> [!TIP]
> With Livewire you need neither: the component is re-rendered on the server. `wire:*` and `x-*` attributes on the tag are passed through untouched.

---

Next: [Component playground](09-playground.md)
