# Malevich 🧑‍🎨

**Stop writing spaghetti `class="{{ ... ? ... : ... }}"` strings in your Blade components.**

Malevich lets you describe every visual option of a component - variant, color, size, or anything else you invent - as a simple, declarative map: *"this option -> these classes."* Then, right in your markup, you just say which option is active. Malevich does the boring part: picks the right classes, merges them, removes duplicates, and hands you back a clean class string.

That's it. No new templating language, no build step, no runtime JS. Just Blade, PHP arrays, and the `$attributes` bag you already know.

### The problem 🫩👎

Every reusable Blade component ends up looking like this sooner or later:

```php
<div @class([
    'inline-flex items-center font-medium',
    'hover:brightness-95' => $variant === 'solid',
    'border-2 border-dashed bg-transparent' => $variant === 'outline',
    'bg-blue-100' => $color === 'primary' && $variant === 'solid',
    ...
])>
    ...
</div>
```

It works - until you need a fourth variant, a second color, or a size prop. Then it's a wall of ternaries nobody wants to touch, buried inside markup that's supposed to be about structure, not logic.

### The solution 🙂👍

Declare the class maps once, near the top of the component. Apply them with one fluent chain:

```php
@variant([
    '*' => 'inline-flex items-center font-medium',
    'solid' => 'hover:brightness-95',
    'outline' => 'border-2 border-dashed bg-transparent',
])

@color([
    'primary' => [
        'text-blue-500',
        'bg-blue-100' => $variant === 'solid',
        'border-blue-200' => $variant === 'outline',
    ],
])

<span {{ $attributes->variant('solid')->color('primary') }}>
    {{ $slot }}
</span>
```

No conditionals in the markup. No string concatenation. Just data in, classes out.

### Why you'll like it 🤔

- **Reads top to bottom.** All the "what classes does this option produce" logic lives in one place, separate from the markup that renders it.
- **Works with anything, not just Tailwind.** Under the hood it's plain `Arr::toCssClasses`, so conditional arrays, BEM classes, whatever - all fine.
- **Multi-element components? No problem.** Named "targets" let a single component style its wrapper, icon, and label independently, each with its own variant/color/size, without prop-name collisions.
- **Reuse combos with presets.** Register a set of directive values once (`@preset`), apply it anywhere with `->preset('name')`.
- **Zero new concepts to learn.** It's just methods on the `ComponentAttributeBag` you already call `$attributes->merge()` on every day.
- **Add your own directives.** Not just `@variant`/`@color`/`@size` - one config line and `@radius`, `@shadow`, whatever you need, gets its own directive and fluent method for free.
- **Framework-native.** No JS, no compiler, no config beyond a single optional file. Install it and it's already working.

If you're building a UI kit or design system in Blade - buttons, badges, alerts, cards - and you're tired of variant logic leaking into your templates, this is exactly the tool you were about to write yourself.

---

## Table of Contents

- [The problem](#the-problem)
- [The solution](#the-solution)
- [Why you'll like it](#why-youll-like-it)
- [Installation](#installation)
- [Basic usage](#basic-usage)
- [Adding your own directives](#adding-your-own-directives)
- [Named targets - styling multiple elements in one component](#named-targets---styling-multiple-elements-in-one-component)
- [Presets - reuse common combinations](#presets---reuse-common-combinations)
- [Slots - merging classes from named slots](#slots---merging-classes-from-named-slots)
- [Best practices & lifehacks](#best-practices--lifehacks)
- [Bundled components](#bundled-components)
- [Configuration reference](#configuration-reference)
- [API summary](#api-summary)
- [License](#license)

---

## Installation

Install the package via Composer:

```bash
composer require malevich/ui
```

Publish the config file (optional, but recommended if you want to customize directives or component paths):

```bash
php artisan vendor:publish --tag malevich:config
```

Publish the bundled components (optional - gives you an editable copy of `<x-ui::primitive>` and friends):

```bash
php artisan vendor:publish --tag malevich:components
```

---

## Basic usage

Here's the smallest complete example - a `<x-badge>` component with a `variant`, `color` and `size`:

```php
{{-- resources/views/components/ui/badge.blade.php --}}

@props([
    'variant' => 'outline',
    'color' => 'primary',
    'size' => 'md',
])

@variant([
    '*' => 'inline-flex items-center font-medium',
    'solid' => 'hover:brightness-95',
    'outline' => 'border-2 border-dashed bg-transparent',
])

@color([
    'primary' => [
        'text-blue-500',
        'bg-blue-100' => $variant === 'solid',
        'border-blue-200' => $variant === 'outline',
    ],
])

@size([
    'md' => 'px-3 py-1 text-sm',
])

<span {{ $attributes->variant($variant)->color($color)->size($size) }}>
    {{ $slot }}
</span>
```

Usage:

```blade
<x-ui.badge variant="outline" color="primary" size="md">
    New
</x-ui.badge>
```

Renders:

```html
<span class="inline-flex items-center font-medium border-2 border-dashed bg-transparent text-blue-500 border-blue-200 px-3 py-1 text-sm">
    New
</span>
```

### How it works

1. `@variant([...])`, `@color([...])`, `@size([...])` - these are **Blade directives** that register a *map* of "option name" -> "classes" for the current component. They accept anything `Illuminate\Support\Arr::toCssClasses` understands, so you can also use the `'class' => condition` array syntax as shown for `@color` above.
2. The special `'*'` key means "always apply these classes, regardless of which option is selected" - handy for base/shared classes.
3. `$attributes->variant($variant)->color($color)->size($size)` picks the matching classes for each directive based on the value you pass in (e.g. `$variant === 'outline'`), merges everything together, deduplicates classes, and returns the final class string as part of the attribute bag.

You can chain as many directives as you like, in any order.

### Shortcut with `use()`

If you have several props to apply at once, `use()` lets you pass them all in a single call instead of chaining every directive manually:

```php
<span {{ $attributes->use(compact('variant', 'color', 'size')) }}>
    {{ $slot }}
</span>
```

This is functionally identical to the chained version above.

---

## Adding your own directives

Out of the box, three directives are registered: `@variant`, `@color`, `@size`. You can add as many custom ones as you need in `config/malevich.php`:

```diff
 'directives' => [
    'variant',
    'size',
    'color',
+   'radius',
 ],
```

Once added, both the Blade directive **and** the attribute-bag method are available automatically:

```php
@radius([
    'full' => 'rounded-full',
])

<span {{ $attributes->variant($variant)->color($color)->size($size)->radius($radius) }}>
    {{ $slot }}
</span>
```

---

## Named targets - styling multiple elements in one component

Sometimes a single component renders several HTML elements (a wrapper, an icon, a label...) and each one needs its own set of variant/color/size classes. Every directive supports an optional **target name** as its first argument. If you don't pass one, it defaults to `default` (configurable via `malevich.default_target`).

```php
@props([
    'variant' => ['wrapper' => 'dashed'],
    'color' => ['wrapper' => 'black', 'foo' => 'orange'],
    'size' => ['wrapper' => 'md', 'foo' => 'md', 'bar' => 'lg'],
])

{{-- Wrapper --}}
@variant('wrapper', [
    'dashed' => 'border-2 border-dashed rounded-2xl font-medium',
])
@color('wrapper', [
    'black' => 'border-black',
])
@size('wrapper', [
    'md' => 'p-4',
])

{{-- Foo --}}
@size('foo', [
    'md' => 'text-sm',
])
@color('foo', [
    'orange' => 'text-orange-500',
])

{{-- Bar --}}
@size('bar', [
    'lg' => 'text-5xl',
])

<div {{ $attributes->for('wrapper')->variant($variant['wrapper'])->color($color['wrapper'])->size($size['wrapper']) }}>
    <div {{ $attributes->for('foo')->color($color['foo'])->size($size['foo']) }}>
        Foo
    </div>
    <div {{ $attributes->for('bar')->size($size['bar']) }}>
        Bar
    </div>
</div>
```

`->for('wrapper')` tells Malevich "resolve classes registered under the `wrapper` target". Each `<div>` in the example only picks up the classes registered for its own target, keeping things fully isolated.

Result:

```html
<div class="border-2 border-dashed rounded-2xl font-medium border-black p-4">
    <div class="text-sm text-orange-500">
        Foo
    </div>
    <div class="text-5xl">
        Bar
    </div>
</div>
```

### Passing one array instead of per-target values

Instead of manually extracting `$variant['foo']`, you can pass the whole array - Malevich will automatically look up the key that matches the current `for()` target:

```php
@props([
    'variants' => ['wrapper' => 'dashed'],
    'colors' => ['wrapper' => 'black', 'foo' => 'orange'],
    'sizes' => ['wrapper' => 'md', 'foo' => 'md', 'bar' => 'lg'],
])

...

<div {{ $attributes->for('wrapper')->variant($variants)->color($colors)->size($sizes) }}>
    <div {{ $attributes->for('foo')->color($colors)->size($sizes) }}>
        Foo
    </div>
    <div {{ $attributes->for('bar')->size($sizes) }}>
        Bar
    </div>
</div>
```

### Same thing, but shorter with `use()`

```php
@props([
    'variant' => ['wrapper' => 'dashed'],
    'color' => ['wrapper' => 'black', 'foo' => 'orange'],
    'size' => ['wrapper' => 'md', 'foo' => 'md', 'bar' => 'lg'],
])

...

<div {{ $attributes->for('wrapper')->use(compact('variant', 'color', 'size')) }}>
    <div {{ $attributes->for('foo')->use(compact('color', 'size')) }}>
        Foo
    </div>
    <div {{ $attributes->for('bar')->use(compact('size')) }}>
        Bar
    </div>
</div>
```

---

## Presets - reuse common combinations

If you find yourself repeating the same directive values across components, register them once as a **preset** and apply them wherever needed.

```php
@props([
    'preset' => 'main',

    // Override just the wrapper color, from black to orange
    'color' => ['wrapper' => 'orange'],
])

@preset('main', [
    'wrapper' => [
        'variant' => 'dashed',
        'color' => 'black',
        'size' => 'md',
    ],
    'foo' => [
        'color' => 'orange',
        'size' => 'md',
    ],
    'bar' => [
        'size' => 'lg',
    ],
])

...

@color('wrapper', [
    'black' => 'border-black',
    'orange' => 'border-orange-500',
])

...

<div {{ $attributes->for('wrapper')->preset('main')->color($color) }}>
    <div {{ $attributes->for('foo')->preset('main') }}>
        Foo
    </div>
    <div {{ $attributes->for('bar')->preset('main') }}>
        Bar
    </div>
</div>
```

> [!IMPORTANT]
> **Order matters:** always call `->preset('name')` **before** any explicit directive calls (`->color(...)`, `->use(...)`, etc.), so the explicit values you pass afterwards can override the preset defaults.

---

## Slots - merging classes from named slots

If a component element should also inherit `class` (and other attributes) passed to a named slot, use `->slot(...)` to tell Malevich which slot's attribute bag to merge in:

```php
{{-- resources/views/components/ui/foo.blade.php --}}

@props([
    'colors' => [],
])

@color('heading', [
    'primary' => 'text-blue-600 font-bold',
])

@color('footer', [
    'secondary' => 'text-gray-400',
])

<div class="p-4 flex flex-col gap-4">
    <h1 {{ $attributes->for('heading')->slot($heading)->color($colors) }}>
        {{ $heading }}
    </h1>

    {{ $slot }}

    <footer {{ $attributes->for('footer')->slot($footer)->color($colors) }}>
        {{ $footer }}
    </footer>
</div>
```

Usage:

```blade
<x-ui.foo :colors="['heading' => 'primary', 'footer' => 'secondary']">
    <x-slot name="heading" class="text-2xl">
        Heading
    </x-slot>
    <main>
        Content
    </main>
    <x-slot name="footer" class="text-xs">
        Footer
    </x-slot>
</x-ui.foo>
```

The `class="text-2xl"` passed to the `heading` slot is merged together with the `text-blue-600 font-bold` classes registered for the `heading` target - without `->slot()`, slot attributes are never picked up automatically, so nothing "leaks" unexpectedly.

---

## Best practices & lifehacks

A set of small, working patterns that make Malevich code shorter, more
readable, and easier to maintain — plus a few gotchas that aren't obvious
from reading the API alone.

### 1. Put everything shared behind `'*'`, don't repeat it per option

```php
// 🚫 base classes duplicated across every variant
@variant([
    'solid'   => 'inline-flex items-center font-medium hover:brightness-95',
    'outline' => 'inline-flex items-center font-medium border-2 border-dashed',
])

// ✅ shared once, each variant only describes the delta
@variant([
    '*'       => 'inline-flex items-center font-medium',
    'solid'   => 'hover:brightness-95',
    'outline' => 'border-2 border-dashed bg-transparent',
])
```

`'*'` always applies, regardless of the chosen value — use it for the
component's "skeleton", not as another variant key.

### 2. Conditional classes as arrays, not ternaries

Every directive goes through `Arr::toCssClasses`, so skip the `? :` string
building entirely and describe the condition next to the class itself:

```php
// 🚫
@color([
    'primary' => 'text-blue-500 ' . ($variant === 'solid' ? 'bg-blue-100' : ''),
])

// ✅
@color([
    'primary' => [
        'text-blue-500',
        'bg-blue-100'     => $variant === 'solid',
        'border-blue-200' => $variant === 'outline',
    ],
])
```

The logic stays declarative and never leaves the section that defines the
color itself.

### 3. Reach for `use()` once the prop count grows

```php
// 🚫 verbose
$attributes->variant($variant)->color($color)->size($size)->radius($radius)

// ✅ scales to any number of directives
$attributes->use(compact('variant', 'color', 'size', 'radius'))
```

Also works with `for()`: `$attributes->for('wrapper')->use(compact('color', 'size'))`.

### 4. Only introduce a target when an element genuinely needs its own axis

Targets are worth it when a sub-element truly has its own set of
variants/colors/sizes (an icon, a label, a wrapper). If every part of the
component shares the same `variant`, don't split it into a target — just
apply it directly on each node:

```php
<div {{ $attributes->for('wrapper')->color($colors['wrapper']) }}>
    <svg {{ $attributes->for('icon')->color($colors['icon']) }} />
</div>
```

Extra targets add indirection without buying you anything.

### 5. Presets: base first, overrides after — and you can stack them

```php
@preset('outline-card', [
    'variant' => 'outline',
    'color'   => 'gray',
    'size'    => 'md',
])

{{-- preset() BEFORE explicit calls, or the override won't stick --}}
<div {{ $attributes->preset('outline-card')->color($color ?? null) }}>
```

Order matters: explicit values called after `preset()` win over it, and
before it get overwritten by it. This also means presets can be **layered**
— apply a base design-system preset, then a theme preset on top, and
whichever directive the second one touches wins:

```php
<div {{ $attributes->preset('card-base')->preset('theme-danger') }}>
```

Useful for a "base + theme" split instead of duplicating full class sets per
theme.

### 6. Use `slot()` only where outer classes should actually land

```php
<h1 {{ $attributes->for('heading')->slot($heading)->color($colors) }}>
    {{ $heading }}
</h1>
```

Without `->slot()`, a named slot's `class`/attributes never leak in
automatically — that's intentional, not a missing step. Add `slot()`
selectively, only on the elements that should be customizable from the
outside via `<x-slot class="...">`.

### 7. Wildcard-only directives can be called with no argument at all

If a directive has nothing to choose between (one fixed set of classes),
don't invent a prop for it — just call it bare:

```php
@color(['*' => 'border-black'])

<div {{ $attributes->color() }}>
```

Handy for purely decorative sub-elements inside a compound component that
don't need their own prop.

### 8. Add a new directive instead of encoding two axes into one

If a single directive's options start encoding two independent concerns
(`'solid-danger'`, `'outline-danger'`, `'solid-brand'`...), that's the
signal to add a directive rather than multiply combinations:

```php
// config/malevich.php
'directives' => ['variant', 'color', 'size', 'tone'],
```

```php
@tone(['danger' => 'ring-red-500', 'brand' => 'ring-blue-500'])

$attributes->variant('outline')->tone('danger')
```

Each directive is one orthogonal styling axis; Malevich merges and
de-duplicates the combination for you.

### 9. Reuse a partially-built `Selector` as a base

`Selector` is immutable — every call returns a new instance, so it's safe to
keep a "base" chain and branch it for different parts of the same component:

```php
$base = $attributes->color($color)->size($size); // shared base

<div {{ $base->for('wrapper')->variant('dashed') }}>
    <span {{ $base->for('icon') }}></span>
</div>
```

`$base` never mutates between calls — `for('wrapper')` and `for('icon')` can
both branch off it safely without affecting one another.

### 10. Leave `default_target` alone unless you actually collide with it

If nothing forces your hand, keep `default_target = 'default'`. That's what
lets the component's root element automatically pick up a `class` passed
from the outside (`<x-badge class="ml-2">`), while named targets
intentionally don't (they aren't the component's root). Only change
`default_target` if you have a real target literally named `default` that
conflicts in meaning.

### 11. `directive()` is your escape hatch for dynamic directive names

If a directive's name comes from a variable (e.g. driven by theme config),
you don't need it to be declared in `malevich.directives` with a magic
method — call it explicitly:

```php
foreach (['variant', 'color', 'size'] as $name) {
    $attributes = $attributes->directive($name, $props[$name] ?? null);
}
```

### 12. Register `@variant/@color/@size` in the order the markup reads

Declare directives in the order they logically show up in the rendered
markup (shape first — `variant`, then `color`, then `size`) rather than the
order your editor's autocomplete suggested. Registration order never
affects the resolved output, but it makes a big difference in how easy the
component is to read six months later.

---

### A few extra ones

### 13. Grab the raw string with `resolveClasses()` when you don't want a full attribute bag

`toHtml()`/`__toString()` wrap the result as `class="..."` (plus any other
attributes on the root). If you just need the plain class string — say, to
feed an Alpine `x-bind:class`, a JS prop, or to concatenate manually — call
`resolveClasses()` directly instead:

```php
<div x-bind:class="{{ json_encode([$attributes->for('icon')->resolveClasses() => true]) }}">
```

### 14. Share a directive block across components with a Blade partial

If several components in your design system reuse the exact same
`@variant`/`@color` maps (e.g. every "surface" component shares the same
color palette), don't copy-paste the directive block — extract it into a
partial and `@include` it:

```php
{{-- resources/views/partials/surface-directives.blade.php --}}
@color([
    'neutral' => 'bg-white text-gray-900',
    'muted'   => 'bg-gray-50 text-gray-600',
])
```

```php
{{-- card.blade.php / panel.blade.php / callout.blade.php --}}
@include('partials.surface-directives')
```

One source of truth for the palette, reused across every component that
needs it, no drift between them over time.

### 15. Don't do expensive work inside a directive's config array

`@variant([...])` / `@color([...])` register their config on **every
render** of the component (it's cheap array building normally, but it does
run every time). Keep the maps to literal arrays and simple expressions;
if a class fragment needs real computation, compute it once above the
directive call and reference the variable instead of inlining logic into
the map itself:

```php
// 🚫 recomputes on every render, buried inside the map
@color(['active' => expensive_lookup($request) ? 'ring-2' : ''])

// ✅ computed once, directive stays a plain lookup
$ring = expensive_lookup($request) ? 'ring-2' : '';
@color(['active' => $ring])
```

### 16. Always call the macros on the exact `$attributes` instance you were given

The registry keys everything off the `ComponentAttributeBag` object's
identity (via `WeakMap`). If you reassign or rebuild `$attributes` (e.g.
`$attributes = $attributes->merge([...])` returns a *new* bag under the
hood in some Laravel versions, or you pass a manually constructed bag into
a sub-view) before calling `->for()`/`->use()`, you can end up resolving
against a bag that never had any directives registered on it, and get back
empty classes. When in doubt, register directives and resolve classes
against the same `$attributes` variable the component method received.

### 17. Build your own macro on top of Malevich's for very common combos

If a "mode" (variant + color + size + preset, all together) repeats across
many components, wrap it in your own macro so call sites don't have to
spell it out every time:

```php
ComponentAttributeBag::macro('dangerButton', function () {
    /** @var \Illuminate\View\ComponentAttributeBag $this */
    return $this->preset('button-base')->variant('solid')->color('danger');
});
```

```blade
<button {{ $attributes->dangerButton() }}>Delete</button>
```

It's just a thin macro over the fluent API Malevich already exposes, but it
turns a recurring combination into a single named call.

---

## Bundled components

### `<x-ui::primitive>`

A small helper component for rendering an arbitrary tag (`div`, `button`, `a`, ...) while still resolving Malevich classes through `:class`.

> [!IMPORTANT]
> Because `<x-ui::primitive>` renders a dynamic tag, you must pass resolved classes explicitly through `:class` - using `{{ $attributes->... }}` directly on the tag will not work here.

```php
@props([
    'variant' => 'solid',
])

@variant([
    '*' => 'active:scale-95 active:translate-y-0.5',
    'solid' => 'px-6 py-2 rounded-xl bg-black text-white font-medium',
])

<x-ui::primitive as="button" type="button" :class="$attributes->variant($variant)">
    {{ $slot }}
</x-ui::primitive>

{{-- or using use() --}}
<x-ui::primitive as="button" type="button" :class="$attributes->use(compact('variant'))">
    {{ $slot }}
</x-ui::primitive>
```

Renders:

```html
<button class="active:scale-95 active:translate-y-0.5 px-6 py-2 rounded-xl bg-black text-white font-medium" type="button">
    test
</button>
```

By default, components are registered under the `ui::` prefix from `resources/views/components/ui` (both your published copy and the package's own copy are auto-registered, so it works even before publishing).

---

## Configuration reference

`config/malevich.php`:

| Key | Default | Description |
|---|---|---|
| `directives` | `['variant', 'size', 'color']` | List of directives to auto-register as both `@directive(...)` Blade directives and `$attributes->directive(...)` methods. |
| `default_target` | `'default'` | Internal name used when `->for()` is not called. Change only if it conflicts with a target name you actually use. |
| `components.path` | `resource_path('views/components/ui')` | Where your published `<x-ui::*>` components live. |
| `components.prefix` | `'ui'` | Tag prefix for the registered components (`<x-ui::primitive>`, etc.). |

---

## API summary

| Method | Description |
|---|---|
| `->for(string $target)` | Switch the target you're resolving classes for (defaults to `default`). |
| `->slot(mixed $slot)` | Merge the `class`/attributes of a given `ComponentSlot` / `ComponentAttributeBag` into the result. |
| `->use(array\|string $choicesOrTarget, array $choices = [])` | Apply several directive values at once, optionally also setting the target. |
| `->directive(string $name, mixed $value)` | Manually set a value for any directive by name (used internally by the generated methods). |
| `->{directiveName}(string $value)` | Auto-generated per configured directive, e.g. `->variant('solid')`, `->color('primary')`. |
| `->preset(string $name)` | Apply a previously registered `@preset(...)` to the current target. |

Blade directives available in your components:

| Directive | Description |
|---|---|
| `@directive($target = null, $config)` | Low-level directive registration (used to build the others). |
| `@preset($name, $config)` | Register a reusable preset of directive values, optionally keyed per target. |
| `@variant(...)`, `@color(...)`, `@size(...)` and any custom directive from config | Register a class map for the current component: `'option' => 'classes'`. Use `'*'` for classes applied regardless of the selected option. |

---

## License

The MIT License (MIT). See [`LICENSE.md`](LICENSE.md) for more information.
