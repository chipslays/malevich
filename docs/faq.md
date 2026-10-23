# FAQ and troubleshooting

## Something doesn't work

### My classes don't show up

Check, in this order:

1. The directive is written **before** `@attributes`.
2. The value matches a key exactly: `size="lg"` needs a `'lg'` key, and keys are case-sensitive. Turn on [strict mode](10-debugging.md#strict-mode) and Malevich tells you which value is wrong and which ones exist.
3. The option has a value at all. Give it a default in `@props` - a `null` default means "not set".
4. You changed `config/malevich.php` or updated Malevich? Run `php artisan view:clear`.

Still nothing? Turn on [debug mode](10-debugging.md#debug-mode): the comment in front of the element shows every value and every class.

### I see `variant="..."` in my HTML

The directive for that option is missing or written after `@attributes`. Malevich only removes option names it knows about.

### `class` from the tag isn't applied to my icon

By design: plain attributes from the tag go to the main element only. Address the inner element with a [targeted attribute](05-inner-elements.md#internal-elements-configure-them-from-the-component-tag) (`icon:class="..."`, `icon:size="lg"`) or put the attributes on its [named slot](05-inner-elements.md#named-slots). A targeted attribute only works for a target the component declares (`@base('icon', ...)`, an option map or a compound rule).

### An optional element renders as an empty `<div>`

It has a `@base`, which makes it non-empty in every variant. Move its classes into the variants and wrap it in [`@styled`](05-inner-elements.md#parts-that-exist-only-in-some-variants).

### `<x-input size="20">` doesn't reach the `<input>`

`size` is an option of that component (it has `@size`), and option names are never printed - the value only picks classes. The same goes for any option named like an HTML attribute (`type`, `width`, `rows`, ...). Name the option differently (`@option('scale', [...])`) to keep the HTML attribute. Strict mode points this out.

### I wrote `class="..."` next to `@attributes` and got an exception

Text and `{{ }}` are fine there. With `{!! !!}` or a Blade directive inside the class, pass the classes as an argument: `@attributes(class: $classes)`.

### The catalog looks unstyled

The catalog loads your app's Vite entries. Run `npm run dev` or `npm run build`. CSS from elsewhere goes into `catalog.head`. See [Component catalog](09-catalog.md#your-styles-and-scripts).

### I changed something and the output didn't change

Blade caches compiled views. Run `php artisan view:clear`.

## General questions

### Can I use it without Tailwind?

Yes. Malevich only puts strings into `class`. BEM, Bootstrap, your own CSS - anything works.

### Does it work with Livewire and Alpine?

Yes. `wire:*`, `x-*` and `@click`-style attributes on the tag are passed through untouched. To switch a variant in the browser, see [JavaScript and CSS hooks](08-javascript.md).

### Can I use `@attributes` in a regular view or layout?

No, only inside Blade components - it needs the component's `$attributes`.

### How do I use `@attributes` on a nested component?

Just put it there: `<x-heroicon-o-check @attributes('icon') />`. The child receives the classes as its `$attributes` and must print them. See [Building components](06-building-components.md).

### My CSS has Tailwind's `@variant dark (...)`. Does Malevich break it?

No. Without parentheses right after the name it is left alone.

### Is it fast?

Directives compile to plain PHP once, like any Blade directive. At render time it's a few array lookups: a button with variants, sizes, a combination and an icon costs about 12 µs more than the same button written by hand (25 µs instead of 13 µs) - around 12 ms for a page with a thousand buttons. Run `composer bench` in the repository to measure on your machine.

### Is it safe with Octane?

Yes. Class maps belong to the component instance and are freed with it, so nothing leaks between requests or workers. Settings like `Malevich::strict()` live in a singleton of the application.
