# Building components

This chapter shows how to build components on top of other components, how to use the unstyled primitive that ships with Malevich, and how to share class maps between components.

## Components on top of components

`@attributes` works on component tags too. Everything it would print on an HTML tag - classes, the user's attributes, `merge:` - is handed to that component as its `$attributes`:

```blade
{{-- resources/views/components/alert.blade.php --}}
@base('flex gap-3 rounded-xl border p-4')
@base('icon', 'size-4 shrink-0')

<div @attributes>
    <x-heroicon-o-information-circle @attributes('icon') />
    {{ $slot }}
</div>
```

```html
<div class="flex gap-3 rounded-xl border p-4">
    <svg class="size-4 shrink-0" ...>...</svg>
    ...
</div>
```

- The child component must print its `$attributes` (`<svg {{ $attributes }}>`, or `@attributes` if it is a Malevich component). Otherwise the classes have nowhere to go.
- Use **single quotes** inside the arguments: `@attributes(merge: ['type' => 'submit'])`.
- For always-on classes of such an element use `@base('icon', '...')`. A `class="..."` on a component tag goes to the child the usual Blade way and doesn't work like `@base`.

## The unstyled primitive

Malevich ships one component without any styles: `<x-malevich::primitive>`. It handles the HTML details every button, link and card gets wrong sooner or later. Use it as the **root of your own components**:

```blade
{{-- resources/views/components/button.blade.php --}}
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

<x-malevich::primitive :as="$as" @attributes>
    {{ $slot }}
</x-malevich::primitive>
```

Your button now does all of this for free:

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

### What it does

| You write | You get | Why |
|---|---|---|
| `as="section"` | `<section>` | Any tag. The default is `div`. |
| `href="/home"` | `<a href="/home">` | Anything with a link is a link. |
| `as="button" href="/home"` | `<a href="/home">` | So `<x-button href="...">` just works. |
| `as="button"` | `<button type="button">` | A plain `<button>` in a form submits it. Pass `type="submit"` when you want that. |
| `href="..." target="_blank"` | `+ rel="noopener noreferrer"` | Safe external links. |
| `as="button" disabled` | `<button disabled data-disabled>` | Native `disabled` where HTML supports it. |
| `href="..." disabled` | `<a aria-disabled="true" tabindex="-1" data-disabled>` | Links can't be disabled, so the `href` is removed and screen readers are told. |
| `as="img" src="..."` | `<img src="..." />` | Void elements are self-closing. |
| `as="script"`, `as="div onclick=..."` | an exception | Only valid, safe tag names are accepted - `as` may come from data. |

Every other attribute (`class`, `id`, `wire:*`, `x-*`, ...) is passed through, and attributes you set always win over these defaults.

**Style `data-disabled`, not `:disabled`.** A disabled element always gets `data-disabled`, link or button. One rule styles both:

```blade
@base('data-disabled:pointer-events-none data-disabled:opacity-50')
```

### How the props travel

- **`as`** is a prop of your button with the default `'button'`, passed on with `:as="$as"`. That's what lets users write `<x-button as="span">`.
- **`href`, `target`, `disabled`, `type`** are *not* in your `@props`. They travel through `@attributes` to the primitive, which knows what to do with them.
- If you do put one of them in `@props` (say, to use `$disabled` in a `@compound`), pass it on yourself: `<x-malevich::primitive :as="$as" :disabled="$disabled" @attributes>`.

### Changing the primitive

Want other defaults? Publish it and edit your copy:

```bash
php artisan vendor:publish --tag malevich:components
```

The file lands in `resources/views/vendor/malevich/components`. Your copy replaces the original, the tag stays `<x-malevich::primitive>`. Delete the file to go back.

> [!NOTE]
> A published copy no longer gets updates from the package. Publish only what you really want to change.

## Sharing styles between components

Class maps are plain Blade, so you can move them into a partial and `@include` it:

```blade
{{-- resources/views/styles/sizes.blade.php --}}
@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])
```

```blade
{{-- button.blade.php, input.blade.php, select.blade.php --}}
@props(['size' => 'md'])

@include('styles.sizes')

<button @attributes>{{ $slot }}</button>
```

Now buttons, inputs and selects always have the same heights.

> [!TIP]
> Keep partials outside `resources/views/components`, otherwise Laravel treats them as components too (`<x-partials.sizes>`) and they show up in the [playground](09-playground.md).

---

Next: [Tailwind](07-tailwind.md)
