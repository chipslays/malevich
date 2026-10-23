# The `$attributes` API

`@attributes` covers almost everything. When you need full control - in PHP, in an Alpine expression, in a loop - the same engine is available as methods on `$attributes`:

```blade
<div {{ $attributes->variant($variant)->size($size) }}>

<svg {{ $attributes->for('icon')->color('gray')->merge(['aria-hidden' => 'true']) }}>

<div x-bind:class="'{{ $attributes->for('panel')->toClasses() }}'">
```

## Methods

| Method | What it does |
|---|---|
| `->variant('x')`, `->color('x')`, `->size('x')`, your own | Set the value of an option. |
| `->option('name', 'x')` | Set any option by name. |
| `->use(['size' => 'lg', 'color' => 'red'])` | Set several values at once. |
| `->use('icon', ['size' => 'lg'])` | Switch to an inner element and set values. |
| `->for('icon')` | Switch to an inner element. |
| `->slot($slot)` | Read HTML attributes and option values from a named slot. |
| `->modifiers('value')` | Read the current target's modifier settings, e.g. `text:value.uppercase.limit[24]`. |
| `->merge([...])` | Default attributes, like `@attributes(merge: ...)`. |
| `->toClasses()` | Only the class string. |
| `->classesFor('variant')` | The class string of every value, for [Alpine](08-javascript.md#2-classesfor---let-the-server-prepare-every-variant). |
| `->only()`, `->except()`, `->has()`, `->get()`, ... | The usual `$attributes` methods, with the classes already applied. |

Every call returns a **new** object, so you can branch safely:

```blade
@php($button = $attributes->size($size))

<div {{ $button }}>
    <span {{ $button->for('icon') }}></span>
</div>
```

## Differences from `@attributes`

- These methods **can't see your template variables**, so pass prop values yourself: `->size($size)`. Attributes on the tag are still read automatically.
- A value you pass wins over everything, including `@props`.
- Slot option values take precedence over targeted attributes; targeted attributes take precedence over shared options. Unlike `@attributes('title')`, the fluent API needs an explicit `->slot($title)` to attach a named slot. See [inner elements](05-inner-elements.md#which-value-wins).
- Start from the `$attributes` the component received. `$attributes->merge([...])` returns a plain Blade bag that knows nothing about your class maps, so write `->variant(...)->merge([...])`, not `->merge([...])->variant(...)`.

When providing a named slot, keep its settings on the slot: `<x-slot:icon size="lg">`. Use `icon:size="lg"` on the component for an icon it creates internally, or when intentionally separating target settings from slot content. See [Slot attributes or targeted attributes?](05-inner-elements.md#slot-attributes-or-targeted-attributes) for examples.

## The `Malevich` facade

Settings you can change in code, usually in `AppServiceProvider::boot()`:

```php
use Malevich\Malevich;

Malevich::strict(! app()->isProduction());   // strict mode
Malevich::debug(app()->isLocal());           // debug mode
Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes));
```

A call in code always wins over the config.

---

More: [Examples](examples.md) · [Cheat sheet](cheat-sheet.md) · [FAQ](faq.md)
