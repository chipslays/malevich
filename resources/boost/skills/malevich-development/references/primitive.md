# Unstyled primitive `<x-malevich::primitive>`

A style-less root for your components. It handles HTML details so components don't have to.

| You write | You get |
|---|---|
| `as="section"` | `<section>` (default `div`) |
| `href="/home"` | `<a href="/home">` |
| `as="button" href="/home"` | `<a href="/home">` |
| `as="button"` | `<button type="button">` (pass `type="submit"` when needed) |
| `href target="_blank"` | adds `rel="noopener noreferrer"` |
| `as="button" disabled` | `<button disabled data-disabled>` (native disabled on button, input, select, textarea, fieldset, option) |
| `href disabled` | `<a aria-disabled="true" tabindex="-1" data-disabled>` without `href` |
| `as="img" src=".."` | self-closing void tag, no content |
| `as="div onclick=.."` | exception: only valid tag names |

Other attributes pass through; attributes you set always win over defaults. Disabled elements always get `data-disabled`: style `data-disabled:opacity-50 data-disabled:pointer-events-none` instead of `disabled:` so links and buttons look the same.

## Building a component on it

```blade
@props(['as' => 'button', 'variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center justify-center rounded-lg font-medium data-disabled:opacity-50')
@variant(['primary' => 'bg-black text-white', 'outline' => 'border border-gray-300'])
@size(['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4'])

<x-malevich::primitive :as="$as" @ui>{{ $slot }}</x-malevich::primitive>
```

- `as` is a prop of your component with a default, forwarded with `:as="$as"`. Hardcoding `as="button"` on the primitive makes it impossible to override.
- `href`, `target`, `disabled`, `type` must **not** be in your `@props`; they travel through `@ui` to the primitive.
- If you declare one in `@props` (e.g. `$disabled` for a `@compound`), forward it: `<x-malevich::primitive :as="$as" :disabled="$disabled" @ui>`.

Usage then works for free: `<x-button href="/x">`, `<x-button href="/x" disabled>`, `<x-button as="span">`, `<x-button type="submit">`.

## Customizing

`php artisan vendor:publish --tag malevich:components` copies it to `resources/views/vendor/malevich/components`; the tag stays `<x-malevich::primitive>`. A published copy no longer receives package updates, so publish only if you must change defaults.
