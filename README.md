<h1 align="center">Malevich 🎨</h1>

<p align="center">
  Variant-driven Blade components without the class spaghetti.
</p>

<p align="center">
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/v/malevich/malevich" alt="Latest version"></a>
  <a href="https://github.com/chipslays/malevich/actions/workflows/php.yml"><img src="https://github.com/chipslays/malevich/actions/workflows/php.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/php-v/malevich/malevich" alt="PHP version"></a>
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/dt/malevich/malevich" alt="Downloads"></a>
</p>

---

## What is it?

You write *which classes belong to which option*. Malevich picks the right ones and puts them on your HTML. That's the whole idea.

```blade
@props(['variant' => 'primary'])

@variant([
    'primary' => 'bg-black text-white',
    'outline' => 'border border-gray-300',
])

<button @ui>{{ $slot }}</button>
```

```blade
<x-button variant="outline">Save</x-button>
```

```html
<button class="border border-gray-300">Save</button>
```

No ternaries, no `{{ $attributes->merge(['class' => ...]) }}`, no JavaScript, no build step. Works with Tailwind or any other CSS.

---

## Contents

**Getting started**

1. [Why?](#why)
2. [Installation](#installation)
3. [Quick start](#quick-start)
4. [How it works (5 words you need to know)](#how-it-works-5-words-you-need-to-know)

**Declaring styles**

5. [`@base` - classes that are always there](#base---classes-that-are-always-there)
6. [Class maps](#class-maps)
7. [`@variant`, `@color`, `@size`](#variant-color-size)
8. [`@directive` - the universal form](#directive---the-universal-form)
9. [`@compound` - classes for a combination](#compound---classes-for-a-combination)
10. [`@preset` - saved combinations](#preset---saved-combinations)
11. [`@defaults` - default values](#defaults---default-values)
12. [`@cases` - one declaration per value](#cases---one-declaration-per-value)
13. [Which tool should I use?](#which-tool-should-i-use)

**Rendering**

14. [`@ui` - full reference](#ui---full-reference)
15. [Where values come from](#where-values-come-from)
16. [Inner elements (targets)](#inner-elements-targets)
17. [Named slots](#named-slots)
18. [Booleans and enums](#booleans-and-enums)

**Components**

19. [Building on other components](#building-on-other-components)
20. [Unstyled primitive](#unstyled-primitive)

**More**

21. [Your own directives](#your-own-directives)
22. [Sharing styles between components](#sharing-styles-between-components)
23. [Fixing Tailwind conflicts](#fixing-tailwind-conflicts)
24. [Data attributes](#data-attributes)
25. [`make:malevich` command](#makemalevich-command)
26. [The `$attributes` API](#the-attributes-api)
27. [Testing components](#testing-components)
28. [Catching typos in CI (`malevich:lint`)](#catching-typos-in-ci-malevichlint)
29. [Configuration](#configuration)
30. [Editor support (Tailwind IntelliSense)](#editor-support-tailwind-intellisense)
31. [A complete example](#a-complete-example)
32. [FAQ and troubleshooting](#faq-and-troubleshooting)
33. [Cheat sheet](#cheat-sheet)
34. [Playground and contributing](#playground-and-contributing)

---

## Why?

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

It works, but every new option makes it harder to read. Malevich splits it into small, flat tables - one per option - and keeps the markup clean:

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

<button @ui>{{ $slot }}</button>
```

## Installation

Needs Laravel 12 (PHP 8.2+) or Laravel 13 (PHP 8.3+). The service provider is registered automatically.

```bash
composer require malevich/malevich
php artisan malevich:install
```

`malevich:install` is optional, but sets everything up in one go:

- publishes `config/malevich.php`;
- configures [Tailwind IntelliSense](#editor-support-tailwind-intellisense) in `.vscode/settings.json` (other settings are kept);
- tells you whether [Tailwind conflicts](#fixing-tailwind-conflicts) are resolved;
- offers ready-made starter components - alert, badge, button, card, stat, switch - as plain Blade files in your components folder.

```bash
php artisan malevich:install --components=button --components=badge   # no questions
php artisan malevich:install --components=all --no-vscode
php artisan malevich:install --force                                  # overwrite config and components
```

Existing files are never overwritten without `--force`.

Publishing the config by hand works too. You need it only to add your own directives or change paths:

```bash
php artisan vendor:publish --tag malevich:config
```

The unstyled package components can be published too, if you want to change them - see [Customizing the primitive](#customizing-the-primitive):

```bash
php artisan vendor:publish --tag malevich:components
```

## Quick start

**1. Generate a component.**

```bash
php artisan make:malevich badge
```

This creates `resources/views/components/badge.blade.php` with an empty block for every directive:

```blade
@base('')

@variant([
    // 'name' => 'classes',
])

@size([
    // 'name' => 'classes',
])

@color([
    // 'name' => 'classes',
])

@defaults([
    // 'variant' => 'name',
    // 'size' => 'name',
    // 'color' => 'name',
])

<div @ui>
    {{ $slot }}
</div>
```

**2. Fill it in.** A badge needs only a color, so we remove `variant` and `size`, give `color` a default, and change `<div>` to `<span>`:

```blade
@base('inline-flex rounded-full px-2 py-0.5 text-xs font-medium')

@color([
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-green-100 text-green-700',
    'red' => 'bg-red-100 text-red-700',
])

@defaults(['color' => 'gray'])

<span @ui>
    {{ $slot }}
</span>
```

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

Notice that you never wrote `$color` in the markup, nor declared it in `@props`: `@ui` read it from the tag by itself and fell back to the [default](#defaults---default-values).

> [!TIP]
> Prefer to create files by hand? That works too - Malevich doesn't care how the file was made. The command just saves typing.

## How it works (5 words you need to know)

| Word | Meaning | Example |
|---|---|---|
| **Option** | Something the component can change. Usually a prop. | `variant`, `color`, `size` |
| **Value** | What the user picked for an option. | `color="green"` -> `green` |
| **Class map** | A table "value -> classes" for one option. Declared with a directive. | `@color(['green' => 'bg-green-100'])` |
| **Base classes** | Classes an element always has, whatever the options. | `@base('rounded-lg')` |
| **Target** | A named inner element of the component. The main element is called `default`. | the `icon` inside a button |
| **`@ui`** | Puts the final classes and attributes on an element. | `<span @ui>` |

What happens when the component renders:

1. Directives (`@base`, `@variant`, `@color`, ...) run first and **remember** what you declared.
2. `@ui` runs. For every remembered option it looks up the current value (on the tag, in your `@props`, in a preset, or in `@defaults`), finds the classes for that value, and adds them.
3. Then it adds `@compound` classes whose conditions match, then the `class` passed from outside, and prints all of it together with the other attributes.

Because of step 1 -> 2, **directives must come before `@ui`** in the file. Put them at the top, right after `@props`.

---

## `@base` - classes that are always there

Most elements have classes that never change: `inline-flex`, `rounded-lg`, `font-medium`. They don't depend on any option, so they don't belong in a class map. Put them in `@base`:

```blade
@base('inline-flex items-center rounded-lg font-medium')
```

That's the main element. For an [inner element](#inner-elements-targets), pass its name first:

```blade
@base('title', 'text-lg font-semibold')
@base('description', 'text-sm text-gray-500')

<div @ui>
    <h3 @ui('title')>{{ $title }}</h3>
    <p @ui('description')>{{ $description }}</p>
</div>
```

Read it as: *"the element `@ui('title')` always has `text-lg font-semibold`"*.

| Form | Meaning |
|---|---|
| `@base('classes')` | Always-on classes of the main element (`@ui`). |
| `@base('title', 'classes')` | Always-on classes of the inner element `@ui('title')`. |

- `@base` classes come **first**, before any option classes.
- You can write `@base` several times for the same element; the classes add up.
- Classes can also be a list or a conditional array: `@base(['flex', 'flex-col' => $vertical])`.

**Why not just write `class="..."` on the tag?** Because the element also gets `class` from `@ui`, and a tag can't have two `class` attributes. With `@base`, all classes of the component live in one place at the top, and the element can still receive classes from a [named slot](#named-slots) or [`merge:`](#ui---full-reference).

## Class maps

A class map is a PHP array: the key is a value of the option, the item is the classes for it.

```blade
@size([
    'sm' => 'h-8 px-3 text-sm',   // added when size is "sm"
    'md' => 'h-10 px-4',          // added when size is "md"
])
```

Rules:

- **Unknown value** (e.g. `size="xl"` but there's no `'xl'` key): nothing is added. No error - unless [strict mode](#strict-mode) is on.
- **`null` value**: same, nothing is added.
- Classes are added in the order the directives appear in the file. Duplicates are removed.

The classes can be written in three ways - use whichever reads best:

```blade
@color([
    // 1. a string
    'red' => 'bg-red-100 text-red-700',

    // 2. a list
    'green' => ['bg-green-100', 'text-green-700'],

    // 3. conditional classes, exactly like Blade's @class
    'blue' => ['bg-blue-100 text-blue-700', 'animate-pulse' => $live],
])
```

> [!NOTE]
> **The `'*'` key.** Inside a class map, `'*'` means "always, whatever the value". `@size(['*' => 'inline-flex', 'sm' => '...'])` does the same as `@base('inline-flex')` + `@size(['sm' => '...'])`. It still works, but `@base` is clearer - prefer it.

## `@variant`, `@color`, `@size`

These three directives come built in. **They work exactly the same way** - the only difference is the name, which is also the name of the prop they read:

| Directive | Reads | Typical use |
|---|---|---|
| `@variant([...])` | `$variant` | overall look: `primary`, `outline`, `ghost` |
| `@color([...])` | `$color` | color scheme: `gray`, `red`, `green` |
| `@size([...])` | `$size` | dimensions: `sm`, `md`, `lg` |

Use as many as the component needs:

```blade
@props(['variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center justify-center rounded-lg font-medium')

@variant([
    'primary' => 'bg-black text-white hover:bg-gray-800',
    'outline' => 'border border-gray-300 hover:bg-gray-50',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])

<button @ui>{{ $slot }}</button>
```

```blade
<x-button variant="outline" size="sm">Cancel</x-button>
```

```html
<button class="inline-flex items-center justify-center rounded-lg font-medium border border-gray-300 hover:bg-gray-50 h-8 px-3 text-sm">Cancel</button>
```

Each of them also accepts a target name as the first argument - see [Inner elements](#inner-elements-targets):

```blade
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])
```

Need `@radius`, `@shadow` or `@tone`? Either use [`@directive`](#directive---the-universal-form) or [register your own](#your-own-directives).

## `@directive` - the universal form

`@variant`, `@color` and `@size` are just shortcuts. Under the hood each of them is `@directive` with the name filled in:

```blade
@variant(['primary' => '...'])
{{-- is exactly the same as --}}
@directive('variant', ['primary' => '...'])
```

So `@directive` lets you create an option with **any name**, right in the component, without touching the config:

```blade
@props(['radius' => 'md'])

@directive('radius', [
    'none' => 'rounded-none',
    'md' => 'rounded-md',
    'full' => 'rounded-full',
])
```

```blade
<x-card radius="full" />
```

All the forms it accepts:

| Form | Meaning |
|---|---|
| `@directive('radius', [...])` | Class map for option `radius` on the main element. |
| `@directive('radius', 'icon', [...])` | Same, for the inner element `icon`. |

The first argument is always the **option name** - the prop that picks the value. The optional second one is the **element**.

**When to use `@directive` instead of a named directive:**

- The option exists in one or two components only (`radius`, `padding`, `orientation`). Not worth adding to the config.
- The option name comes from a variable: `@directive($name, [...])`.

**When not to:** for classes that don't depend on any option, use [`@base`](#base---classes-that-are-always-there).

If an option shows up in many components, [register it](#your-own-directives) to get a short `@radius([...])` instead.

## `@compound` - classes for a combination

A class map answers "what classes for **this one value**?". Sometimes that's not enough, because a class depends on **two or more options at once**.

Example: a badge has `variant` (`soft` / `solid`) and `color` (`gray` / `red`). The text color depends only on `color`, but the background depends on **both**:

| | gray | red |
|---|---|---|
| **soft** | `bg-gray-100` | `bg-red-100` |
| **solid** | `bg-gray-700` | `bg-red-600` |

You can't put `bg-red-100` into `@color` (it would apply to solid too) or into `@variant` (it would apply to gray too). That's what `@compound` is for:

```blade
@props(['variant' => 'soft', 'color' => 'gray'])

@base('rounded-full px-2 text-xs')

@variant([
    'solid' => 'text-white',
])

@color([
    'gray' => 'text-gray-700',
    'red' => 'text-red-700',
])

@compound(['variant' => 'soft', 'color' => 'gray'], 'bg-gray-100')
@compound(['variant' => 'soft', 'color' => 'red'], 'bg-red-100')
@compound(['variant' => 'solid', 'color' => 'gray'], 'bg-gray-700')
@compound(['variant' => 'solid', 'color' => 'red'], 'bg-red-600')

<span @ui>{{ $slot }}</span>
```

Read each line as a sentence: *"if variant is soft **and** color is red, add `bg-red-100`"*.

```blade
<x-badge variant="solid" color="red">Overdue</x-badge>
```

```html
<span class="rounded-full px-2 text-xs text-white text-red-700 bg-red-600">Overdue</span>
```

**Syntax:**

```blade
@compound([conditions], classes)
@compound('target', [conditions], classes)   {{-- for an inner element --}}
```

- `conditions` - `['option' => value, ...]`. **All** of them must match.
- A value can be a list, meaning **"any of these"**:

  ```blade
  @compound(['variant' => ['outline', 'ghost'], 'color' => 'red'], 'hover:bg-red-50')
  ```

- `classes` - a string, list or conditional array, just like in a class map.
- Compound classes are added **after** all class maps.

**`@compound` also works with props that have no class map.** That makes it the easiest way to react to a single flag:

```blade
@props(['size' => 'md', 'loading' => false])

@compound(['loading' => true], 'cursor-wait opacity-75')
@compound(['loading' => true, 'size' => 'sm'], 'gap-1')
```

**When to use `@compound`:**

- A class depends on 2+ options (the table above).
- A class depends on a flag that doesn't deserve its own class map (`loading`, `active`, `invalid`).

**When not to:** if the class depends on one option only, put it into that option's class map instead.

## `@preset` - saved combinations

A preset gives a **name to a set of values**, so the user can write one word instead of several props:

```blade
@preset('featured', ['variant' => 'elevated', 'padding' => 'lg'])
@preset('compact', ['variant' => 'flat', 'padding' => 'sm'])

@base('rounded-2xl bg-white')

@variant([
    'flat' => 'border',
    'elevated' => 'shadow-xl',
])

@directive('padding', [
    'sm' => 'p-3',
    'md' => 'p-6',
    'lg' => 'p-10',
])

@defaults(['variant' => 'flat', 'padding' => 'md'])

<div @ui>{{ $slot }}</div>
```

```blade
<x-card>...</x-card>                                 {{-- border p-6 (the defaults) --}}
<x-card preset="featured">...</x-card>               {{-- shadow-xl p-10 --}}
<x-card preset="featured" padding="sm">...</x-card>  {{-- shadow-xl p-3 --}}
```

How it works:

- The preset is chosen by the `preset` attribute on the tag (or a `preset` prop).
- A preset only provides **fallback values**. Any value the user passes explicitly wins - that's why `padding="sm"` beats the preset's `lg`.
- A preset wins over [`@defaults`](#defaults---default-values) - that's why `preset="featured"` is elevated, not flat.

> [!IMPORTANT]
> Put the defaults of a component with presets in [`@defaults`](#defaults---default-values), **not** in `@props`. A `@props` default is a template variable, so it counts as "explicitly set" and always wins over a preset: with `@props(['variant' => 'flat'])`, `preset="featured"` would stay flat. If you need an option in `@props` (to use `$variant` in the markup), give it a `null` default.

A preset can hold values for inner elements too. Use target names as keys (`default` is the main element):

```blade
@preset('danger', [
    'default' => ['color' => 'red'],
    'icon' => ['color' => 'red', 'size' => 'lg'],
])
```

**When to use presets:** a few combinations repeat all over your app ("the danger button", "the featured card"). For one-off looks, just pass the props.

## `@defaults` - default values

`@defaults` says which value an option takes when nobody picked one - the same idea as `defaultVariants` in CVA:

```blade
@variant([
    'primary' => 'bg-black text-white',
    'ghost' => 'hover:bg-gray-100',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

@defaults(['variant' => 'primary', 'size' => 'md'])

<button @ui>{{ $slot }}</button>
```

```blade
<x-button>Save</x-button>                {{-- primary, md --}}
<x-button size="sm">Save</x-button>      {{-- primary, sm --}}
```

A default is the **last** fallback: a value on the tag, in your `@props` or in a [preset](#preset---saved-combinations) always wins over it (see [Where values come from](#where-values-come-from)). That is what makes it different from a `@props` default, which beats presets.

- A default applies to **every element** of the component, just like an attribute on the tag. `size="md"` by default styles the main element and its `@size('icon', [...])` alike.
- Give an inner element its own default with a target name first: `@defaults('icon', ['size' => 'sm'])`. It wins over the shared one for that element only. A per-target array works too: `@defaults(['size' => ['default' => 'md', 'icon' => 'sm']])`.
- Defaults take part in [`@compound`](#compound---classes-for-a-combination) conditions like any other value.
- You can write `@defaults` several times; later values replace earlier ones.
- Defaults are not props: the option still doesn't exist as `$variant` in the template. If the markup needs it (`@if ($variant === 'ghost')`), declare it in `@props` with a `null` default - `@ui` will still fall back to `@defaults`.

`@defaults` is a fixed name, like `@base` and `@preset`.

## `@cases` - one declaration per value

`@variant('title', [...])` groups classes **by element**. When a component has many elements and every option value changes most of them ("teal" and "graphite" stat cards), the same classes read better grouped **by value**:

```blade
@props(['variant' => 'teal'])

@base('flex')
@base('title', 'font-medium')

@cases('variant', [
    'teal' => [
        'default' => 'bg-teal-600',
        'title' => 'text-teal-100',
        'icon' => 'text-teal-200',
    ],
    'graphite' => [
        'default' => 'bg-gray-900',
        'title' => 'text-white',
        'icon' => 'text-white/40',
    ],
])

<div @ui>
    <svg @ui('icon')></svg>
    <h2 @ui('title')>{{ $title }}</h2>
</div>
```

`@cases('variant', [...])` fills exactly the same slots as one `@variant('target', [...])` per element: `variant="teal"` styles the main element, `icon` and `title`, each with its own classes. Everything else (`@ui`, presets, `@compound`, [named slots](#named-slots)) works as before.

- The first argument is the option name: `@cases('size', [...])`, `@cases('color', [...])`, any [`@directive`](#directive---the-universal-form) name.
- Keys of the inner array are targets. `default` is the main element, like in presets and per-target values.
- A value can also be plain classes (a string or a list) - they style the main element: `'teal' => 'bg-teal-600'`.
- Several `@cases` calls for one option add up. A `@variant([...])` written **after** `@cases('variant', ...)` for the same element replaces it, so keep one style per option.

> [!NOTE]
> Write conditional classes (`['a', 'b' => $cond]`) under a target key, never directly as a value: a string-keyed array is read as a list of targets.

The name `@cases` can be changed with the `cases_directive` [config option](#configuration).

## Which tool should I use?

| I want to... | Use |
|---|---|
| Change classes depending on one prop | [`@variant` / `@color` / `@size`](#variant-color-size), or [`@directive('name', ...)`](#directive---the-universal-form) for any other name |
| Add classes that are always there | [`@base('...')`](#base---classes-that-are-always-there) |
| Add fixed classes to an inner element | [`@base('title', '...')`](#base---classes-that-are-always-there) |
| Style many elements at once, grouped by the value of one prop | [`@cases`](#cases---one-declaration-per-value) |
| Render an element only in some variants | [`@has`](#parts-that-exist-only-in-some-variants-has) |
| Add classes when two props have certain values | [`@compound`](#compound---classes-for-a-combination) |
| React to a boolean flag (`loading`, `active`) | [`@compound(['loading' => true], ...)`](#compound---classes-for-a-combination) or a [`true`/`false` class map](#booleans-and-enums) |
| Give a name to a common set of props | [`@preset`](#preset---saved-combinations) |
| Pick a value when the user didn't | [`@defaults`](#defaults---default-values) |
| Style an inner element (icon, title, spinner) | a [target](#inner-elements-targets): `@size('icon', [...])` + `@ui('icon')` |
| Let users add classes to a slot | [`@ui('title')` + `<x-slot:title class="...">`](#named-slots) |
| Add default attributes (`type`, `role`, `aria-*`) | [`@ui(merge: [...])`](#ui---full-reference) |
| Build a component on top of another one | [`<x-other @ui />`](#building-on-other-components) |
| Get correct button/link/disabled behaviour for free | [`<x-malevich::primitive>`](#unstyled-primitive) |
| Use the same option in many components | [register it in the config](#your-own-directives) |
| Reuse the same class maps in many components | an [`@include` partial](#sharing-styles-between-components) |

---

## `@ui` - full reference

`@ui` prints the attributes of one element: the final `class` plus everything else that belongs there. Put it inside an HTML tag, like Blade's own `@class`:

```blade
<button @ui>...</button>
```

### All forms

| You write | What it does |
|---|---|
| `@ui` | Main element: classes + all attributes from the component tag. |
| `@ui('icon')` | Inner element `icon`: only its classes. |
| `@ui(merge: ['type' => 'button'])` | Main element + default attributes. |
| `@ui('title')` + a `<x-slot:title>` | Inner element + attributes of the slot with the same name (automatic). |
| `@ui('heading', slot: $title)` | Inner element + attributes of a differently named slot. |
| `@ui('title', slot: false)` | Inner element, ignore the slot's attributes. |
| `@ui('title', merge: ['id' => 'x'])` | Inner element + default attributes. |

### Arguments

`@ui` takes three optional arguments. The first one can be written without a name, the other two are named (PHP named arguments):

| Argument | Type | Default | Meaning |
|---|---|---|---|
| target (1st) | `string` | the main element | Which element to render. See [targets](#inner-elements-targets). |
| `slot:` | a named slot or `false` | the slot named like the target | Take extra attributes (`class`, `id`, ...) from this slot. `false` = don't. See [named slots](#named-slots). |
| `merge:` | `array` | `[]` | Default attributes for this element. |

### `merge:` in detail

`merge:` sets attributes that the element should have **unless the user says otherwise**:

```blade
<button @ui(merge: ['type' => 'button', 'data-component' => 'button'])>
```

```blade
<x-button>Go</x-button>
{{-- <button class="..." type="button" data-component="button"> --}}

<x-button type="submit">Go</x-button>
{{-- <button class="..." type="submit" data-component="button">  - user's type wins --}}
```

- Attributes from the tag **replace** `merge:` attributes with the same name.
- A `class` in `merge:` is **added**, not replaced: `merge: ['class' => 'select-none']`.
- Values can be dynamic: `merge: ['aria-checked' => $checked ? 'true' : 'false']`.

This is the same thing as `$attributes->merge([...])` in plain Blade.

### What ends up in the HTML

For the **main element** (`@ui`, `@ui(merge: ...)`):

1. `class` - in this order: `@base` -> class maps (in file order) -> `@compound` classes -> `class` from `merge:` -> `class` from the tag. Duplicates removed.
2. All other attributes from the tag: `id`, `wire:click`, `x-data`, `data-*`, `aria-*`, ...
3. Attributes from `merge:` that the tag didn't set.

**Not** printed: option names (`variant`, `color`, `size`, any `@directive` name) and `preset`. They were consumed by Malevich, so you won't get `<button variant="outline">` - even if you forgot to list them in `@props`.

For an **inner element** (`@ui('icon')`): only its own classes, plus `merge:` attributes and the attributes of its [slot](#named-slots). Attributes from the component tag never go to inner elements.

If there are no classes at all, no empty `class=""` is printed.

### Rules

- `@ui` works only **inside a component file** (it needs `$attributes`).
- **Declare directives before `@ui`.** A directive written after `@ui` is not known to it yet.
- `@ui` works on HTML tags **and on other components**: `<x-icon @ui('icon') />`. The classes and attributes are passed to that component as its `$attributes`. See [Building on other components](#building-on-other-components).
- Inside `<x-...>` tags, write arguments with single quotes: `@ui(merge: ['type' => 'submit'])`. A double quote would end the generated attribute.
- You can use `@ui` as many times as you want in one component, including several times for the same target.
- A `class="..."` on the same tag is **added after** the element's own classes, as on a component tag: `<span @ui('chip') class="size-4 {{ $extra }}">`. Static text and `{{ }}` are supported. For anything else (`{!! !!}`, `@if` inside the class, or `merge:` already in use) Malevich throws on compile - pass the classes through `merge: ['class' => ...]` instead.

> [!NOTE]
> The name `@ui` can be changed with the `render_directive` [config option](#configuration).

## Where values come from

For every option, Malevich takes the **first** value it finds:

| # | Source | Example |
|---|---|---|
| 1 | An explicit call in the [`$attributes` API](#the-attributes-api) | `$attributes->size('sm')` |
| 2 | A variable with the same name in the template - normally your `@props` | `@props(['size' => 'md'])` |
| 3 | An attribute with the same name on the component tag (when it's not in `@props`) | `<x-button size="sm">` |
| 4 | The active [preset](#preset---saved-combinations) | `preset="featured"` |
| 5 | [`@defaults`](#defaults---default-values) | `@defaults(['size' => 'md'])` |

What that means in practice:

- Put the **default** of an option in `@defaults`. It is used only when nothing else picked a value, so presets keep working.
- An option doesn't need to be in `@props` at all - the attribute is read directly. Add it to `@props` only when the markup needs `$size`, and then with a `null` default.
- A non-null `@props` default is a template variable (row 2): it beats the tag's preset. That is the one reason to prefer `@defaults`.
- `null` means "not set" and falls through to the next source.
- A value that matches no key of the class map adds nothing. Turn on [strict mode](#strict-mode) to get an exception instead.

## Inner elements (targets)

Components often have more than one element: a button with a spinner, an alert with an icon and a title. Each of them can have its own class maps.

**1. Give the class map a target name** as the first argument:

```blade
@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3',
    'md' => 'size-4',
])
```

**2. Render the element with `@ui('spinner')`.**

Full example:

```blade
@props(['size' => 'md', 'loading' => false])

{{-- main element --}}
@base('inline-flex items-center gap-2')

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

{{-- inner element "spinner" --}}
@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3',
    'md' => 'size-4',
])

<button @ui>
    @if ($loading)
        <svg @ui('spinner') viewBox="0 0 24 24">...</svg>
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

**Different values for different targets.** Pass an array keyed by target name. The main element is called `default`:

```blade
<x-button :size="['default' => 'lg', 'spinner' => 'sm']" />
```

**Things to know:**

- Any directive takes a target: `@color('icon', ...)`, `@base('title', ...)`, `@compound('icon', [...], ...)`, and presets can be keyed by target.
- Targets only get **their own** classes. `class="..."` and other attributes from the component tag always go to the main element.
- To let the user style an inner element from outside, use a [named slot](#named-slots).

### Parts that exist only in some variants (`@has`)

An element with no classes at all usually means "this part is not drawn in this variant". `@has('target')` renders its body only when the element ends up with at least one class:

```blade
@cases('variant', [
    'teal' => ['default' => 'bg-teal-600', 'glow' => 'absolute blur-3xl bg-teal-300/30'],
    'graphite' => ['default' => 'bg-gray-900'],
])

<div @ui>
    @has('glow')
        <div @ui('glow')></div>
    @endhas
</div>
```

It is a normal conditional: `@else` and `@endhas` work, and it takes the same arguments as `@ui` (`@has('glow', merge: [...])`).

> [!IMPORTANT]
> `@base('glow', ...)` is always there, so a part with a `@base` is never "empty". For an optional part put **all** of its classes into the variants, as above.

The name `@has` can be changed with the `has_directive` [config option](#configuration).

## Named slots

Blade lets users pass attributes to named slots: `<x-slot:title class="text-2xl">`. Malevich puts them on the element automatically - **if the element and the slot have the same name**:

```blade
@props(['title' => null])

@base('title', 'text-lg font-semibold')

<div @ui>
    @if ($title)
        <h3 @ui('title')>{{ $title }}</h3>
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

`@ui('title')` saw a slot called `$title` and took its attributes. The slot's `class` is added after the element's own classes, other attributes are passed through.

How the matching works:

- It only reacts to a **named slot** (`<x-slot:title>`). If `title` is a plain string prop (`<x-card title="Billing" />`), nothing happens - a string has no attributes.
- Slot attributes exist only when the user writes them on `<x-slot:...>`, so nothing is added unless they asked for it.

**The slot has a different name than the element?** Pass it explicitly with `slot:`:

```blade
<h3 @ui('heading', slot: $title)>{{ $title }}</h3>
```

**Don't want the slot's attributes on the element?** Turn it off with `slot: false`:

```blade
<h3 @ui('title', slot: false)>{{ $title }}</h3>
```

## Booleans and enums

**Booleans.** `true` and `false` are looked up as the keys `'true'` and `'false'`:

```blade
@props(['checked' => false])

@base('h-6 w-11 rounded-full')

@directive('checked', [
    'true' => 'bg-green-500',
    'false' => 'bg-gray-300',
])

<span @ui></span>
```

```blade
<x-switch />                       {{-- bg-gray-300 --}}
<x-switch checked />               {{-- bg-green-500 --}}
<x-switch :checked="$user->active" />
```

The same works in conditions: `@compound(['checked' => true], '...')`.

**Enums.** A backed enum is looked up by its value, a plain enum by its case name:

```php
enum Status: string
{
    case Paid = 'paid';
    case Overdue = 'overdue';
}
```

```blade
@color([
    'paid' => 'bg-green-100',
    'overdue' => 'bg-red-100',
])
```

```blade
<x-badge :color="$invoice->status" />
```

Numbers work too: `:level="2"` is looked up as `'2'`.

---

## Building on other components

`@ui` also works on component tags. Everything it would print on an HTML tag - classes, the user's attributes, `merge:` - is handed to that component as its `$attributes` instead:

```blade
{{-- resources/views/components/ui/alert.blade.php --}}
@base('flex gap-3 rounded-xl border p-4')
@base('icon', 'size-4 shrink-0')

<div @ui>
    <x-heroicon-o-information-circle @ui('icon') />
    {{ $slot }}
</div>
```

```html
<div class="flex gap-3 rounded-xl border p-4">
    <svg class="size-4 shrink-0" ...>...</svg>
    ...
</div>
```

This is how you build components on top of each other - including on the unstyled [primitive](#unstyled-primitive) that ships with Malevich.

Things to know:

- The child component must print its `$attributes` (`<svg {{ $attributes }}>`, or `@ui` if it's a Malevich component too). Otherwise the classes have nowhere to go.
- Inside a component tag, use **single quotes** in the arguments: `@ui(merge: ['type' => 'submit'])`.
- Everything else works the same: targets, slots, `merge:`.

## Unstyled primitive

Malevich ships one component without any styles: `<x-malevich::primitive>`. It's a smart base element - it handles the HTML details that every button, link and card gets wrong sooner or later, so your components don't have to.

```blade
<x-malevich::primitive>Hi</x-malevich::primitive>
```

```html
<div>Hi</div>
```

You will rarely use it directly in pages. It's meant to be the **root of your own components**.

### What it does

| You write | You get | Why |
|---|---|---|
| `as="section"` | `<section>` | Any tag. Default is `div`. |
| `href="/home"` | `<a href="/home">` | Anything with a link is a link. |
| `as="button" href="/home"` | `<a href="/home">` | A button with `href` becomes a link, so `<x-button href="...">` just works. |
| `as="button"` | `<button type="button">` | A plain `<button>` inside a `<form>` submits it - `type="button"` prevents surprises. Pass `type="submit"` when you need it. |
| `href="..." target="_blank"` | `+ rel="noopener noreferrer"` | Safe external links. |
| `as="button" disabled` | `<button disabled data-disabled>` | Native `disabled` where HTML supports it (button, input, select, textarea, fieldset, option). |
| `href="..." disabled` | `<a aria-disabled="true" tabindex="-1" data-disabled>` (no `href`) | Links can't be disabled natively, so the link is removed and screen readers are told it's disabled. |
| `as="img" src="..."` | `<img src="..." />` | Void elements are self-closing and never get content. |
| `as="div onclick=..."` | an exception | Only valid tag names are accepted. |
| `as="script"` | an exception | Tags that run code or load resources (`script`, `style`, `iframe`, `object`, `embed`, `link`, `meta`, `base`, ...) are refused, so an `as` that comes from data can't inject them. |

Every other attribute (`class`, `id`, `wire:*`, `x-*`, ...) is passed through. Attributes you set always win over the defaults above - e.g. your own `type` or `rel`.

### `data-disabled` for styling

A disabled element always gets `data-disabled`, whatever the tag. Style that instead of `:disabled`, and disabled links look the same as disabled buttons:

```blade
@base('data-disabled:pointer-events-none data-disabled:opacity-50')
```

### Building a button on it

```blade
{{-- resources/views/components/ui/button.blade.php --}}
@props(['as' => 'button', 'variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center justify-center rounded-lg font-medium data-disabled:opacity-50')

@variant([
    'primary' => 'bg-black text-white',
    'outline' => 'border border-gray-300',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

<x-malevich::primitive :as="$as" @ui>
    {{ $slot }}
</x-malevich::primitive>
```

Your button now gets all of the above for free:

```blade
<x-button>Save</x-button>
<x-button type="submit">Send</x-button>
<x-button href="/pricing" variant="outline">Pricing</x-button>
<x-button href="https://laravel.com" target="_blank">Docs</x-button>
<x-button href="/pricing" disabled>Soon</x-button>
<x-button as="span">Not interactive</x-button>
```

```html
<button class="..." type="button">Save</button>
<button class="..." type="submit">Send</button>
<a class="..." href="/pricing">Pricing</a>
<a class="..." href="https://laravel.com" target="_blank" rel="noopener noreferrer">Docs</a>
<a class="..." data-disabled="" aria-disabled="true" tabindex="-1">Soon</a>
<span class="...">Not interactive</span>
```

How the props travel:

- **`as`** is a prop of the button with the default `'button'`, passed on with `:as="$as"`. That's what lets the user write `<x-button as="span">`. If you wrote `as="button"` directly on the primitive instead, it would always win and the user couldn't change it.
- **`href`, `target`, `disabled`, `type`** and everything else are *not* in the button's `@props`. They travel through `@ui` to the primitive, which knows what to do with them.
- If you declare one of them in `@props` yourself (e.g. to use `$disabled` in a `@compound`), it stops travelling - pass it on explicitly: `<x-malevich::primitive :as="$as" :disabled="$disabled" @ui>`.

### Customizing the primitive

Want different defaults - say, no `type="button"`, or extra ARIA attributes? Publish the package components into your project and edit them:

```bash
php artisan vendor:publish --tag malevich:components
```

The files land in `resources/views/vendor/malevich/components`. Malevich always looks there first, so your copy replaces the original while the tag stays the same: `<x-malevich::primitive>`. Delete the file to go back to the package version.

> [!NOTE]
> A published file no longer receives updates from the package. Publish only what you actually want to change, and compare it with the package version after upgrading Malevich.

## Your own directives

If an option appears in many components, give it its own short directive. Publish the config and add the name:

```php
// config/malevich.php
'directives' => ['variant', 'size', 'color', 'radius'],
```

Now `@radius` works exactly like `@variant` (including the target form), and `$attributes->radius()` exists in the [`$attributes` API](#the-attributes-api):

```blade
@props(['radius' => 'md'])

@radius([
    'md' => 'rounded-md',
    'full' => 'rounded-full',
])
```

New components created with [`make:malevich`](#makemalevich-command) will include a `@radius` block too.

> [!NOTE]
> Blade compiles views once and caches them. After changing the `directives` list, run `php artisan view:clear`.

Not every name is allowed. Malevich throws an exception on boot if a name:

- is a built-in Blade directive (`if`, `class`, `foreach`, `props`, ...) - it would replace that directive in your whole app;
- is already registered as a Blade directive by your application or another package (a Blade directive is global, the last one wins and silently breaks the other);
- is a Malevich directive (`base`, `compound`, `preset`, `defaults`, `directive`, or your `render_directive`, `cases_directive`, `has_directive`);
- is already a method of `$attributes` (`merge`, `get`, `only`, ...) - the fluent call `$attributes->merge(...)` could never reach it;
- contains anything but letters, digits and `_`.

### Name collisions

The names of Malevich's own directives are rarely a problem, but they are all configurable or checked:

- `@ui`, `@cases` and `@has` can be renamed with `render_directive`, `cases_directive` and `has_directive`.
- `@base`, `@compound`, `@preset`, `@defaults` and `@directive` are fixed. On boot Malevich checks that Blade or another package has not taken them and throws a clear exception if so.
- A directive that needs an expression (`@variant([...])`, `@cases('x', [...])`, `@base('...')`) and is written **without parentheses** is left alone. So CSS inside a template survives: Tailwind's `@variant dark (...)` is not touched.

## Sharing styles between components

Class maps are plain Blade, so you can move them into a partial and `@include` it:

```blade
{{-- resources/views/components/ui/partials/sizes.blade.php --}}
@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])
```

```blade
{{-- button.blade.php, input.blade.php, select.blade.php --}}
@props(['size' => 'md'])

@include('components.ui.partials.sizes')

<button @ui>{{ $slot }}</button>
```

Now buttons, inputs and selects always have matching heights.

## Fixing Tailwind conflicts

By default Malevich only removes duplicate classes. If the component has `px-4` and the user passes `class="px-8"`, both end up in the HTML, and the result depends on Tailwind's CSS order - not on your intent.

Install [tailwind-merge](https://github.com/gehrisandro/tailwind-merge-laravel) and the user's classes always win - there is nothing to configure, Malevich picks it up by itself:

```bash
composer require gehrisandro/tailwind-merge-laravel
```

```blade
<x-button class="px-8">   {{-- class="... px-8", the component's px-4 is gone --}}
```

The plain PHP package (`gehrisandro/tailwind-merge-php`) is detected as well. To keep only de-duplication even with tailwind-merge installed, set `'merge_classes' => false` in the [config](#configuration).

Need something else? `mergeClassesUsing()` takes any function from the final class string to a new one and always wins over the automatic choice:

```php
// app/Providers/AppServiceProvider.php
use Malevich\Malevich;

public function boot(): void
{
    Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes));
}
```

## Data attributes

Turn on `data_attributes` in the [config](#configuration) and the main element tells everybody which options it got:

```php
'data_attributes' => true,
```

```blade
<x-button variant="danger" size="lg" loading>Save</x-button>
```

```html
<button class="..." data-variant="danger" data-size="lg" data-loading>Save</button>
```

- Inner elements can react to the parent without props: `<svg class="group-data-[variant=danger]:text-white">` (with `group` on the button), or CSS like `[data-variant=danger] .icon`.
- Tests and Dusk get stable selectors: `button[data-variant=danger]`.
- Only options the component declares a class map for are written, and only on the main element.
- A boolean is written as `data-loading` when true and left out when false, so Tailwind's `data-loading:` variant matches exactly when the flag is on.
- An attribute you pass yourself (`data-variant="custom"`) wins.

## `make:malevich` command

```bash
php artisan make:malevich button            # resources/views/components/button.blade.php
php artisan make:malevich forms/input       # resources/views/components/forms/input.blade.php
php artisan make:malevich button --force    # overwrite an existing file
```

- Without `--force` it never overwrites an existing component.
- The `.blade.php` suffix is optional; `forms.input` and `forms/input` are the same.
- The file gets an empty `@base`, an empty class map for **every directive in your config** (including your own) and a `@defaults` block with a commented-out line for each. Delete what you don't need.
- The folder is `components.path` from the [config](#configuration). After creating, the command tells you the tag to use.

Which tag? It depends on `components.prefix`:

| `prefix` | File | Tag |
|---|---|---|
| `null` (default) | `button.blade.php` | `<x-button>` |
| `null` | `forms/input.blade.php` | `<x-forms.input>` |
| `'ui'` | `ui/button.blade.php` | `<x-ui::button>` or `<x-ui.button>` |

## The `$attributes` API

`@ui` covers almost everything. If you need full control, the same engine is available as methods on `$attributes`:

```blade
<div {{ $attributes->variant($variant)->size($size) }}>
<svg {{ $attributes->for('icon')->color('gray')->merge(['aria-hidden' => 'true']) }}>
<div x-bind:class="'{{ $attributes->for('panel')->toClasses() }}'">
```

| Method | What it does |
|---|---|
| `->variant('x')`, `->color('x')`, `->size('x')`, your own | Set the value of an option. |
| `->directive('name', 'x')` | Set the value of any option by name. |
| `->use(['size' => 'lg', 'color' => 'red'])` | Set several values at once. |
| `->use('icon', ['size' => 'lg'])` | Switch to a target and set values. |
| `->for('icon')` | Switch to a target. |
| `->preset('name')` | Apply a preset. Can be called several times, the last one wins. |
| `->slot($slot)` | Add the attributes of a named slot. |
| `->merge([...])` | Default attributes, same as `@ui(merge: ...)`. |
| `->toClasses()` | Return only the class string. |
| `->only()`, `->except()`, `->has()`, `->get()`, `->whereStartsWith()`, ... | Regular `$attributes` methods, with the classes already applied. |

Every call returns a **new** object, so you can safely branch:

```blade
@php($base = $attributes->size($size))

<div {{ $base }}>
    <span {{ $base->for('icon') }}></span>
</div>
```

**Differences from `@ui`:**

- These methods **can't see your template variables**, so pass prop values yourself: `->size($size)`. (Attributes on the tag are still read automatically.)
- Explicit values win over everything, including `@props`.
- Call them on the `$attributes` the component received. `$attributes->merge([...])` returns a new bag that knows nothing about your class maps, so write `->variant(...)->merge([...])`, not `->merge([...])->variant(...)`.

## Testing components

Malevich adds class assertions to Laravel's test views, components and responses. They pass when **one element** has **all** of the classes, in any order and among any others:

```php
$this->blade('<x-button variant="danger">Delete</x-button>')
    ->assertSeeClasses('bg-red-600 text-white')
    ->assertDontSeeClasses('bg-zinc-900');

$this->get('/settings')->assertSeeClasses(['rounded-2xl', 'shadow-xl']);
```

With Pest there is an expectation too:

```php
expect(Blade::render('<x-badge color="green">New</x-badge>'))->toHaveClasses('bg-green-100 text-green-700');
```

A failure lists the class attributes it found, so a missing or misspelled class is easy to spot. Combine it with [strict mode](#strict-mode) in your `TestCase` to catch values a component doesn't know.

## Catching typos in CI (`malevich:lint`)

[Strict mode](#strict-mode) catches `variant="primay"` when the page is rendered. `malevich:lint` catches it before that, in every view, including pages nobody opened:

```bash
php artisan malevich:lint
```

```text
  ✗ resources/views/settings.blade.php:14  <x-button>
    variant [primay] is not defined; expected one of: primary, outline, ghost. Did you mean [primary]?

   ERROR  1 problem in 212 tags of 9 components.
```

It reads your components without rendering them, collects the values every option accepts (keys of the class maps, values tested by `@compound`, maps in `@include`d partials) and the preset names, then checks every `<x-...>` tag in your views. The exit code is `1` when something is wrong, so it can fail a build:

```yaml
# .github/workflows/ci.yml
- run: php artisan malevich:lint --format=github   # problems show up as annotations on the pull request
```

```bash
php artisan malevich:lint resources/views/admin          # only some folders or files
```

**It never guesses.** Only what it can read for sure is checked, everything else is skipped:

- Values written as literals are checked: `variant="primary"`. Bound and echoed ones are not: `:variant="$v"`, `variant="{{ $v }}"`.
- A class map that isn't a literal (`@variant($variants)`) turns off the check for that option; a `@directive($name, ...)`, an unreadable `@compound` or a conditional partial (`@includeIf`) turns it off for the whole component.
- An option whose map is a plain string (`@color('text-red-500')`) accepts any value.
- `loading="false"` next to a `['true' => '...']` map is fine, like in strict mode.

So a clean run doesn't prove there are no typos at all - it proves there are none in what can be checked, which with [literal class names](#class-maps) is nearly everything.

## Configuration

`config/malevich.php`:

| Key | Default | What it does |
|---|---|---|
| `directives` | `['variant', 'size', 'color']` | Option directives: `@variant`, `@size`, ... Add your own here. |
| `render_directive` | `'ui'` | The name of `@ui`. Change it if it clashes with another package. |
| `cases_directive` | `'cases'` | The name of [`@cases`](#cases---one-declaration-per-value). |
| `has_directive` | `'has'` | The name of [`@has`](#parts-that-exist-only-in-some-variants-has). |
| `default_target` | `'default'` | Name of the main element in per-target arrays and presets. |
| `components.path` | `resource_path('views/components')` | Where `make:malevich` creates components. |
| `components.prefix` | `null` | Tag prefix for that folder: `null` -> `<x-button>`, `'ui'` -> `<x-ui::button>`. |
| `data_attributes` | `false` | Put `data-variant="..."` & co. on the main element. See [Data attributes](#data-attributes). |
| `merge_classes` | `'auto'` | `'auto'`: resolve Tailwind conflicts with tailwind-merge when it's installed. `false`: only remove duplicates. See [Fixing Tailwind conflicts](#fixing-tailwind-conflicts). |

### Strict mode

By default a value with no matching key renders nothing, silently: a typo like `size="lg "` or `variant="primay"` just leaves the element unstyled. In development you usually want to hear about it. Turn on strict mode in `AppServiceProvider::boot()`, the same way as Eloquent's `Model::shouldBeStrict()`:

```php
use Malevich\Malevich;

public function boot(): void
{
    Malevich::strict(! app()->isProduction());
}
```

Now an unknown value throws `Malevich\Exceptions\UnknownValueException`:

```text
Malevich: size [xl] is not defined; expected one of: sm, md, lg.
```

It checks the value wherever it came from - the tag, `@props`, a preset or `@defaults` - so a typo in a preset is caught too. It stays quiet when:

- the value is `null` (not set) or `'*'`;
- the element has no class map for that option, or a map with only a `'*'` key;
- the value is known to the component elsewhere: a key of the option's map on **another** element (`@cases` where only some values style an optional part), or a value one of its `@compound` conditions tests for;
- the value is the missing side of a boolean: `['true' => '...']` with `false`.

Keep it off in production: there an unstyled element is better than an error page.

## Editor support (Tailwind IntelliSense)

The [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss) extension only looks for classes in `class="..."` and a few known places. To get autocomplete, hover previews and color swatches inside `@base`, `@variant` & co., tell it where the classes are. `php artisan malevich:install` does it for you (with your own directives included), or add this to `.vscode/settings.json` by hand:

```json
{
    "tailwindCSS.includeLanguages": {
        "blade": "html"
    },
    "tailwindCSS.experimental.classRegex": [
        [
            "@(?:base|variant|color|size|directive|compound|cases)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)",
            "(?:^|=>|[\\[(,])\\s*[\"']([^\"']*)[\"'](?!\\s*=>)"
        ]
    ]
}
```

How it works: the first pattern finds the directive and its arguments, the second picks the strings inside that hold classes - values, not the `'primary' =>` keys.

- Added [your own directives](#your-own-directives)? Add their names to the first pattern: `(?:base|variant|color|size|directive|compound|cases|radius)`.
- Reload the window after changing the settings (**Developer: Reload Window**).
- The same `classRegex` setting works in other editors that use the Tailwind language server (e.g. PhpStorm's Tailwind plugin, Zed, Neovim).

## A complete example

A button with variants, sizes, a combination, a loading spinner, a flag and default attributes - everything from this README in one file:

```blade
{{-- resources/views/components/ui/button.blade.php --}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
])

@base('inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors disabled:opacity-50')

@variant([
    'primary' => 'bg-zinc-900 text-white hover:bg-zinc-700',
    'outline' => 'border border-zinc-300 bg-white hover:bg-zinc-50',
    'danger' => 'bg-red-600 text-white hover:bg-red-500',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])

@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3.5',
    'md' => 'size-4',
    'lg' => 'size-5',
])

@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')
@compound(['loading' => true], 'cursor-wait')

<button @ui(merge: ['type' => 'button'])>
    @if ($loading)
        <svg @ui('spinner') viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" />
        </svg>
    @endif

    {{ $slot }}
</button>
```

```blade
<x-button>Save</x-button>
<x-button variant="outline" size="sm">Cancel</x-button>
<x-button variant="danger" loading wire:click="delete">Deleting</x-button>
<x-button size="lg" type="submit" class="w-full">Sign up</x-button>
```

More examples (badge, alert, card, switch) live in [`workbench/resources/views/components`](workbench/resources/views/components).

## FAQ and troubleshooting

**My classes don't show up.**
Check, in this order:
1. The directive is written **before** `@ui`.
2. The value really matches a key: `size="lg"` needs a `'lg'` key. Keys are case-sensitive. Turn on [strict mode](#strict-mode) and Malevich tells you which value is wrong and which ones exist.
3. The option has a value at all: give it one in [`@defaults`](#defaults---default-values). A `null` `@props` default means "not set", so only `@base` classes apply.
4. After changing `config/malevich.php`, run `php artisan view:clear`.

**My preset is ignored.**
The option has a non-null `@props` default, which wins over presets. Move the default to [`@defaults`](#defaults---default-values) and drop the option from `@props` (or give it a `null` default).

**I see `variant="..."` in my HTML.**
The directive for that option is missing or written after `@ui`. Malevich only removes option names it knows about.

**I wrote `class="..."` next to `@ui` and got an exception.**
On a plain tag it is merged into `@ui` for you, but only for static text and `{{ }}`. With `{!! !!}`, a Blade directive inside the class, or an existing `merge:`, use `@ui('x', merge: ['class' => $classes])`.

**An optional element renders as an empty `<div>`.**
It has a `@base`, which makes it non-empty in every variant. Move its classes into the variants and wrap it in [`@has`](#parts-that-exist-only-in-some-variants-has).

**Tailwind's `@variant dark (...)` inside my `<style>` in a Blade file.**
Fine. Without parentheses it is left as it is.

**`class` from the tag isn't applied to my icon.**
By design: tag attributes go to the main element only. Use a [named slot](#named-slots) or a separate prop.

**How do I use `@ui` on a nested Blade component?**
Just put it there: `<x-heroicon-o-check @ui('icon') />`. The child component receives the classes as its `$attributes`, so it must print them (`{{ $attributes }}`) - every well-written component does. See [Building on other components](#building-on-other-components).

**I changed something and the output didn't change.**
Blade caches compiled views. Run `php artisan view:clear`, especially after updating Malevich or editing `config/malevich.php`.

**Can I use it without Tailwind?**
Yes. Malevich just puts strings into `class`. BEM, Bootstrap, your own CSS - anything works.

**Does it work with Livewire and Alpine?**
Yes. `wire:*`, `x-*` and `@click`-style attributes on the tag are passed through untouched.

**Is it fast? Is it safe with Octane?**
Directives compile to plain PHP once, like any Blade directive. At render time it's a few array lookups: a button with variants, sizes, a combination and an icon costs about 15% more than the same button written by hand with `$attributes->class([...])` (run `composer bench` to measure on your machine). Class maps belong to the component instance and are freed with it, so nothing leaks between requests or Octane workers.

**Can two components use the same preset name?**
Yes. Presets, like class maps, belong to the component that declares them.

**Can I use `@ui` in a regular view or layout?**
No, only in Blade components - it needs the component's `$attributes`.

## Cheat sheet

```blade
@props(['loading' => false, 'title' => null])   {{-- only what the markup itself uses --}}

{{-- always-on classes --}}
@base('inline-flex items-center')

{{-- value -> classes --}}
@variant(['primary' => '...', 'outline' => '...'])
@size(['sm' => '...', 'md' => '...'])

{{-- any option name --}}
@directive('radius', ['md' => 'rounded-md', 'full' => 'rounded-full'])

{{-- inner element "icon" --}}
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])

{{-- always-on classes for inner element "title" --}}
@base('title', 'font-semibold')

{{-- the same, grouped by value: one entry per option value, a class per element --}}
@cases('variant', [
    'primary' => ['default' => '...', 'icon' => '...', 'title' => '...'],
    'outline' => ['default' => '...', 'icon' => '...'],
])

{{-- classes when ALL conditions match; array = any of --}}
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')
@compound(['variant' => ['outline', 'ghost']], 'bg-transparent')
@compound(['loading' => true], 'cursor-wait')

{{-- named set of values, used as preset="big" --}}
@preset('big', ['size' => 'lg'])

{{-- value when nothing else picked one (below tag, @props and presets) --}}
@defaults(['variant' => 'primary', 'size' => 'md'])
@defaults('icon', ['size' => 'sm'])

<button @ui(merge: ['type' => 'button'])>
    <svg @ui('icon')></svg>
    <span @ui('title')>{{ $title }}</span>   {{-- picks up <x-slot:title class="..."> --}}
    @has('badge')<i @ui('badge')></i>@endhas {{-- only when the element has classes --}}
    {{ $slot }}
</button>
```

```bash
php artisan malevich:install
php artisan make:malevich button
php artisan malevich:lint
php artisan vendor:publish --tag malevich:config
php artisan vendor:publish --tag malevich:components
```

## Playground and contributing

The repository ships a small Laravel app with example components (button, badge, alert, card, switch), so you can see everything in a browser:

```bash
composer install
composer serve
```

Open http://127.0.0.1:8000/playground. Components live in `workbench/resources/views/components`, the page is `workbench/resources/views/playground.blade.php`. Edit and refresh.

Before sending a pull request:

```bash
composer lint      # fix code style (Pint)
composer analyse   # static analysis (PHPStan)
composer test      # tests (Pest)
composer check     # all three, without fixing
composer bench     # rendering cost compared to a hand-written component
```

## License

MIT. See [LICENSE.md](LICENSE.md).
