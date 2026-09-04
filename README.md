# Malevich 🧑‍🎨

**Stop writing spaghetti `class="{{ ... ? ... : ... }}"` strings in your Blade components.**

Malevich lets you describe every visual option of a component - variant, color, size, or anything else you invent - as a simple, declarative map: *"this option -> these classes."* Then, right in your markup, you just say which option is active. Malevich does the boring part: picks the right classes, merges them, removes duplicates, and hands you back a clean class string.

That's it. No new templating language, no build step, no runtime JS. Just Blade, PHP arrays, and the `$attributes` bag you already know.

### The problem 🫩

Every reusable Blade component ends up looking like this sooner or later:

```php
class="inline-flex items-center font-medium
    {{ $variant === 'solid' ? 'hover:brightness-95' : '' }}
    {{ $variant === 'outline' ? 'border-2 border-dashed bg-transparent' : '' }}
    {{ $color === 'primary' && $variant === 'solid' ? 'bg-blue-100' : '' }}
    ..."
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
    'primary' => 'bg-blue-100 text-blue-700',
])

<span {{ $attributes->variant($variant)->color($color) }}>
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
- **Add your own directives.** Not just `variant`/`color`/`size` - one config line and `@radius`, `@shadow`, whatever you need, gets its own directive and fluent method for free.
- **Framework-native.** No JS, no compiler, no config beyond a single optional file. Install it and it's already working.

If you're building a UI kit or design system in Blade - buttons, badges, alerts, cards - and you're tired of variant logic leaking into your templates, this is exactly the tool you were about to write yourself.

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

## Lifehacks

The default target is called `default` (configurable via `malevich.default_target`). This makes a few shortcuts possible.

**Wildcard-only directives** don't need any option value passed at all:

```php
@variant(['*' => 'border-2 border-dashed rounded-2xl font-medium'])
@color(['*' => 'border-black'])
@size(['*' => 'p-4'])

<div {{ $attributes->variant()->color()->size() }}>
    ...
</div>
{{-- <div class="border-2 border-dashed rounded-2xl font-medium border-black p-4">...</div> --}}
```

This is exactly equivalent to:

```php
<div {{ $attributes->for('default')->variant('*')->color('*')->size('*') }}>
    ...
</div>
```

You can also call just the directives you need - for example, if you only care about size:

```php
<div {{ $attributes->size() }}>
    ...
</div>
{{-- <div class="p-4">...</div> --}}
```

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

The MIT License (MIT). See `LICENSE` for more information.
