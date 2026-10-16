# Component catalog

Open **`/malevich`** in your local app and you see every component rendered with every value of every option. No stories to write, nothing to register.

```text
http://localhost:8000/malevich
```

## What's on the page

- **A list of your components** on the left, with search (press `/`).
- **Default** - the component as `<x-button>` renders it, with no options.
- **One row per option** - `variant="primary"`, `variant="outline"`, ... and `checked` / `:checked="false"` for booleans.
- **A matrix** of the first two options (`variant` × `size`) - every combination at once. Click a cell to copy its tag.
- On every preview: **copy the tag** and **show the HTML** it renders to.
- **Light / dark** and a dotted or plain background.

A component that fails to render shows its error in place of the preview, so one broken component never breaks the page.

## Which components show up

Every Blade component that uses `@attributes` shows up by itself - also a fresh one from `make:malevich` with empty class maps, with just its default preview. The catalog reads your component files the same way as [`malevich:lint`](11-testing.md#catching-typos-before-the-page-is-rendered) - without running them - to find the values of every option.

Values have to be written out to be found: `@size(['sm' => '...', 'lg' => '...'])` is read, `@size($sizes)` isn't - such an option gets no row of its own. The package's own components (`<x-malevich::primitive>`) are left out.

## Making previews look real

The text inside each preview comes from the usage example in the component's first comment - the one `make:malevich` writes for you:

```blade
{{--
    <x-alert color="red">
        <x-slot:title>Payment failed</x-slot:title>
        Your card was declined.
    </x-alert>
--}}

@props(['color' => 'blue'])
...
```

Without such a comment the text is the component's name: `Button`.

## Your styles and scripts

The previews are styled by **your project**, not by the catalog. The page loads the same Vite entries as your app - the `input` in `vite.config.js`:

```js
// vite.config.js
laravel({
    input: ['resources/css/app.css', 'resources/js/app.js'],
})
```

So your Tailwind theme (`--color-primary`, fonts, plugins) and your JavaScript (Alpine) work exactly as on your pages. Just keep `npm run dev` running, or run `npm run build`. Without a `vite.config.js` the Laravel defaults `resources/css/app.css` and `resources/js/app.js` are used.

CSS from somewhere else? Tell the catalog what to put into its `<head>`:

```php
// config/malevich.php
'catalog' => [
    'head' => '<link rel="stylesheet" href="/css/app.css">',
],
```

The catalog's own interface has its own scoped styles and never touches the previews.

**Dark mode.** The dark button adds the `dark` class to `<html>`. Your components follow it if your Tailwind dark mode is class-based:

```css
/* resources/css/app.css */
@custom-variant dark (&:where(.dark, .dark *));
```

## Where it is available

Only when the app runs in the `local` environment - never in production by accident.

| `.env` | Result |
|---|---|
| nothing | on in `local`, off everywhere else |
| `MALEVICH_CATALOG=true` | on everywhere - protect it with `catalog.middleware`, e.g. `['web', 'auth']` |
| `MALEVICH_CATALOG=false` | off |

The URL is `catalog.path` (`malevich` by default). All settings are in [Configuration](13-configuration.md).

---

Next: [Debugging](10-debugging.md)
