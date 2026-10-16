# Malevich documentation

Malevich lets you build Blade components with variants - `primary` / `outline`, `sm` / `lg`, `red` / `green` - without a single `if` in the markup. You write *which classes belong to which value*, Malevich puts the right ones on the HTML.

```blade
@props(['color' => 'gray'])

@color([
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-green-100 text-green-700',
])

<span @attributes>{{ $slot }}</span>
```

```blade
<x-badge color="green">Paid</x-badge>   {{-- <span class="bg-green-100 text-green-700">Paid</span> --}}
```

## Start here

New to Malevich? Read these three pages in order. Twenty minutes, and you know everything you need for most components.

1. [Getting started](01-getting-started.md) - install it and build your first component.
2. [Classes for options](02-classes.md) - `@base`, `@variant`, `@size`, `@color` and `@option`.
3. [Combinations](03-combinations.md) - classes that depend on two options at once.

## Guides

4. [`@attributes` in detail](04-attributes.md) - what ends up on the element, default attributes.
5. [Inner elements](05-inner-elements.md) - style the icon, the title, the spinner; named slots; optional parts.
6. [Building components](06-building-components.md) - components on top of components, the unstyled primitive, shared styles.
7. [Tailwind](07-tailwind.md) - class conflicts and editor autocomplete.
8. [JavaScript and CSS hooks](08-javascript.md) - data attributes, switching variants with Alpine.

## Tools

9. [Component catalog](09-catalog.md) - every component with every variant on one page.
10. [Debugging](10-debugging.md) - debug mode and strict mode.
11. [Testing and CI](11-testing.md) - class assertions and `malevich:lint`.
12. [Artisan commands](12-commands.md) - `malevich:install`, `make:malevich`, `malevich:lint`.

## Reference

13. [Configuration](13-configuration.md) - every config key, your own options, renaming directives.
14. [The `$attributes` API](14-attributes-api.md) - the same engine as PHP methods.

- [Examples](examples.md) - complete components to copy.
- [Cheat sheet](cheat-sheet.md) - everything on one screen.
- [FAQ and troubleshooting](faq.md) - when something doesn't work.

Upgrading from 1.x? See [UPGRADE.md](../UPGRADE.md).
