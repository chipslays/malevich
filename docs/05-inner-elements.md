# Inner elements

Components often have more than one element: a button with a spinner, an alert with an icon and a title. Each of them can have its own classes. In Malevich such an element is called a **target**, and the main element is called `default`.

## Styling an inner element

Two steps:

1. Give the directive the element's name as the first argument.
2. Print the element with `@attributes('name')`.

```blade
@props(['size' => 'md', 'loading' => false])

{{-- the button itself --}}
@base('inline-flex items-center gap-2')

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

{{-- the spinner inside it --}}
@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3',
    'md' => 'size-4',
])

<button @attributes>
    @if ($loading)
        <svg @attributes('spinner') viewBox="0 0 24 24">...</svg>
    @endif
    {{ $slot }}
</button>
```

```blade
<x-button size="sm" loading>Saving</x-button>
```

```html
<button class="inline-flex items-center gap-2 h-8 px-3 text-sm">
    <svg class="animate-spin size-3" viewBox="0 0 24 24">...</svg>
    Saving
</button>
```

One `size="sm"` styled **both** elements, each with its own classes.

- Every directive takes an element name: `@color('icon', [...])`, `@base('title', '...')`, `@compound('icon', [...], '...')`.
- `class="..."` and other attributes from the component tag always go to the **main** element, never to inner ones. To let users style an inner element, use a [named slot](#named-slots).

**A different value per element.** Pass an array keyed by element name; the main one is `default`:

```blade
<x-button :size="['default' => 'lg', 'spinner' => 'sm']" />
```

## Named slots

Blade lets users put attributes on named slots: `<x-slot:title class="text-2xl">`. Malevich puts them on the element automatically - **when the element and the slot have the same name**:

```blade
@props(['title' => null])

@base('title', 'text-lg font-semibold')

<div @attributes>
    @if ($title)
        <h3 @attributes('title')>{{ $title }}</h3>
    @endif

    {{ $slot }}
</div>
```

```blade
<x-card>
    <x-slot:title class="text-2xl" id="billing">Billing</x-slot:title>
    ...
</x-card>
```

```html
<h3 class="text-lg font-semibold text-2xl" id="billing">Billing</h3>
```

- The slot's `class` is added after the element's own classes; other attributes are passed through.
- It only reacts to a **named slot**. A plain string prop (`<x-card title="Billing" />`) has no attributes, so nothing happens.

The slot has another name? Pass it: `@attributes('heading', slot: $title)`. Don't want the slot's attributes? `@attributes('title', slot: false)`.

## Parts that exist only in some variants

Sometimes an element is drawn only in some variants - a glow behind the "teal" card, a dot in the "live" badge. `@has('name') ... @endhas` renders its content only when that element ends up with at least one class:

```blade
@cases('variant', [
    'teal' => ['default' => 'bg-teal-600', 'glow' => 'absolute blur-3xl bg-teal-300/30'],
    'graphite' => ['default' => 'bg-gray-900'],
])

<div @attributes>
    @has('glow')
        <div @attributes('glow')></div>
    @endhas
</div>
```

`variant="teal"` draws the glow, `variant="graphite"` doesn't.

- It's a normal condition: `@else` works too.
- It takes the same arguments as `@attributes`: `@has('glow', merge: [...])`.

> [!IMPORTANT]
> `@base('glow', '...')` is there in every variant, so the element is never "empty". Put **all** classes of an optional part into the variants, as above. Classes written in `class="..."` on the element itself don't count, so they are fine.

---

Next: [Building components](06-building-components.md)
