<h1 align="center">Malevich ⬛</h1>

<p align="center">
  Variant-driven Blade components without the class spaghetti.
</p>

<p align="center">
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/v/malevich/malevich" alt="Latest version"></a>
  <a href="https://github.com/chipslays/malevich/actions/workflows/php.yml"><img src="https://github.com/chipslays/malevich/actions/workflows/php.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/php-v/malevich/malevich" alt="PHP version"></a>
  <a href="https://packagist.org/packages/malevich/malevich"><img src="https://img.shields.io/packagist/dt/malevich/malevich" alt="Downloads"></a>
</p>

<p align="center">
  <a href="docs/README.md"><b>Documentation</b></a> ·
  <a href="docs/01-getting-started.md">Getting started</a> ·
  <a href="docs/examples.md">Examples</a> ·
  <a href="docs/cheat-sheet.md">Cheat sheet</a>
</p>

---

You write **which classes belong to which option**. Malevich picks the right ones and puts them on your HTML. That's the whole idea.

```blade
{{-- resources/views/components/button.blade.php --}}
@props(['variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center rounded-lg font-medium')

@variant([
    'primary' => 'bg-black text-white',
    'outline' => 'border border-gray-300',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
])

<button @attributes>{{ $slot }}</button>
```

```blade
<x-button variant="outline" size="sm" class="mt-4">Cancel</x-button>
```

```html
<button class="inline-flex items-center rounded-lg font-medium border border-gray-300 h-8 px-3 text-sm mt-4">Cancel</button>
```

No ternaries, no `$attributes->class([...])` chains, no JavaScript, no build step. Works with Tailwind or any other CSS.

## Installation

Laravel 12 (PHP 8.2+) or Laravel 13 (PHP 8.3+):

```bash
composer require malevich/malevich
```

Optionally, publish the config and set up Tailwind autocomplete in VS Code:

```bash
php artisan malevich:install
```

Or publish only what you need:

```bash
php artisan vendor:publish --tag malevich:config       # config/malevich.php
php artisan vendor:publish --tag malevich:components   # the unstyled primitive, to change it
```

Create your first component and open the [Getting started](docs/01-getting-started.md) guide:

```bash
php artisan make:malevich button
```

Or copy the [ready-made kit button](resources/kit/README.md) into your project:

```bash
php artisan malevich:add button
```

The command uses your configured components directory and preserves existing files unless `--force` is explicit. The kit button requires Tailwind CSS 4 with the named color scales and `brand` radius described in its documentation.

## What you get

- **Flat class maps** instead of nested conditions - [`@variant`, `@size`, `@color`, `@option`](docs/02-classes.md).
- **Combinations** - classes for "primary *and* large" with [`@compound`](docs/03-combinations.md).
- **Inner elements** - style the icon, the title, the spinner, and let users style them through [named slots](docs/05-inner-elements.md).
- **A smart primitive** - links, buttons and `disabled` done right, [unstyled](docs/06-building-components.md#the-unstyled-primitive).
- **Component catalog** - every component with every variant at [`/malevich`](docs/09-catalog.md), styled by your own CSS.
- **Debug mode** - an HTML comment that [explains every class](docs/10-debugging.md#debug-mode).
- **Strict mode and lint** - typos like `variant="primay"` caught [while rendering](docs/10-debugging.md#strict-mode) or [in CI](docs/11-testing.md#catching-typos-before-the-page-is-rendered).
- **Test helpers** - `->assertSeeClasses('bg-red-600 text-white')` in [any order](docs/11-testing.md).
- **Tailwind-friendly** - [tailwind-merge](docs/07-tailwind.md#class-conflicts) picked up automatically, editor autocomplete, [Alpine](docs/08-javascript.md) support.

## Documentation

Start with [Getting started](docs/01-getting-started.md), then read on in order - each chapter builds on the previous one. The full table of contents is in [docs/README.md](docs/README.md).

## Playground and contributing

The repository ships a small Laravel app with the ready-made components in the component catalog:

```bash
composer install
composer serve
```

Open http://127.0.0.1:8000/malevich. The components live in `resources/kit` - edit one and refresh. The playground has no Vite build, so it loads Tailwind from a CDN; in your app the catalog uses your own build.

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
