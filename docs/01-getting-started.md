# Getting started

In this chapter you install Malevich, build a badge component and learn the five words the rest of the docs use.

## Why Malevich?

A reusable Blade button usually ends up like this:

```blade
<button {{ $attributes->class([
    'inline-flex items-center rounded-lg font-medium',
    'bg-black text-white' => $variant === 'primary',
    'border border-gray-300' => $variant === 'outline',
    'h-8 px-3 text-sm' => $size === 'sm',
    'h-10 px-4' => $size === 'md',
    'shadow-lg' => $variant === 'primary' && $size === 'lg',
]) }}>
```

It works, but every new option makes it harder to read. With Malevich the same button is a few small tables, one per option, and the markup stays clean:

```blade
@base('inline-flex items-center rounded-lg font-medium')

@variant([
    'primary' => 'bg-black text-white',
    'outline' => 'border border-gray-300',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')

<button @attributes>{{ $slot }}</button>
```

No JavaScript, no build step. It works with Tailwind or any other CSS - Malevich only puts strings into `class`.

## Install

You need Laravel 12 (PHP 8.2+) or Laravel 13 (PHP 8.3+).

```bash
composer require malevich/malevich
```

That's it - Malevich works right away, without any configuration.

Optionally, set up the project in one go:

```bash
php artisan malevich:install
```

It publishes `config/malevich.php`, sets up [Tailwind autocomplete in VS Code](07-tailwind.md#autocomplete-in-your-editor) and tells you whether [Tailwind conflicts](07-tailwind.md#class-conflicts) are handled. It creates no components - those are yours.

Or publish only what you need:

```bash
php artisan vendor:publish --tag malevich:config       # config/malevich.php
php artisan vendor:publish --tag malevich:components   # the unstyled primitive, to change it
```

More in [Artisan commands](12-commands.md).

## Your first component

For a complete button instead of an empty scaffold, run `php artisan malevich:add button`. It copies the [kit component](../resources/kit/README.md) into your configured components folder; existing files are preserved unless you pass `--force`. It needs Tailwind CSS 4 and the kit's [theme tokens](../resources/kit/README.md#theme-tokens): publish them with `php artisan vendor:publish --tag=malevich:theme`. The example below walks through creating your own component from scratch.

**1. Create the file.**

```bash
php artisan make:malevich badge
```

You get `resources/views/components/badge.blade.php` with an empty block for every option. You can also create the file by hand - Malevich doesn't care.

**2. Fill it in.** A badge needs only a color:

```blade
@props(['color' => 'gray'])

@base('inline-flex rounded-full px-2 py-0.5 text-xs font-medium')

@color([
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-green-100 text-green-700',
    'red' => 'bg-red-100 text-red-700',
])

<span @attributes>
    {{ $slot }}
</span>
```

Read it from top to bottom:

- `@props(['color' => 'gray'])` - the badge has a `color`, and it is `gray` unless you say otherwise.
- `@base(...)` - classes the badge always has.
- `@color([...])` - which classes each color adds.
- `<span @attributes>` - "put the final classes here".

**3. Use it.**

```blade
<x-badge>Draft</x-badge>
<x-badge color="green">Paid</x-badge>
<x-badge color="red" class="ml-2">Overdue</x-badge>
```

```html
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">Draft</span>
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">Paid</span>
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 ml-2">Overdue</span>
```

You never wrote `$color` in the markup. `@attributes` read it from the tag - or used the `@props` default when the tag had none. And the `class="ml-2"` from the tag was added at the end, as you would expect.

## Five words

The rest of the docs uses these words:

| Word | Meaning | Example |
|---|---|---|
| **Option** | Something the component can change. Usually a prop. | `variant`, `color`, `size` |
| **Value** | What was picked for an option. | `color="green"` - the value is `green` |
| **Class map** | A table "value -> classes" for one option. | `@color(['green' => 'bg-green-100'])` |
| **Base classes** | Classes an element always has, whatever the options. | `@base('rounded-lg')` |
| **Target** | A named inner element: the icon in a button, the title in a card. | `@attributes('icon')` |

## How it works

When the component renders:

1. The directives at the top (`@base`, `@color`, ...) run first and **remember** what you declared. They print nothing.
2. `@attributes` runs. For every option it finds the value, looks up its classes and builds the final `class`.
3. It prints `class` together with every other attribute from the tag (`id`, `wire:click`, `x-data`, ...).

Because of step 1 -> 2 there is one rule to remember:

> **Declare directives before `@attributes`.** Put them at the top of the file, right after `@props`.

## Try it in a browser

Open `/malevich` in your local app: the [component playground](09-playground.md) shows every component with every value of every option, without any setup.

---

Next: [Classes for options](02-classes.md)
