# Classes for options

This chapter covers the directives you will use in almost every component: `@base` for classes that never change, and class maps (`@variant`, `@size`, `@color`, `@option`) for classes that depend on an option.

## `@base` - classes that are always there

Some classes never change: `inline-flex`, `rounded-lg`, `font-medium`. Put them in `@base`:

```blade
@base('inline-flex items-center rounded-lg font-medium')

<button @attributes>{{ $slot }}</button>
```

- `@base` classes come **first**, before any option classes.
- You can write `@base` several times; the classes add up.
- Classes can be a string, a list, or a conditional array like Blade's `@class`: `@base(['flex', 'flex-col' => $vertical])`.

For an [inner element](05-inner-elements.md), put its name first:

```blade
@base('title', 'text-lg font-semibold')

<div @attributes>
    <h3 @attributes('title')>{{ $title }}</h3>
</div>
```

### Or just `class="..."`

Inside a component, a `class` written next to `@attributes` works exactly like `@base`: it comes first, and the option classes go on top.

```blade
<button class="inline-flex rounded-lg" @attributes>   {{-- same as @base('inline-flex rounded-lg') --}}
```

Use whichever reads better. `@base` keeps all the classes at the top of the file; `class=""` keeps them on the element. Both together work too - `@base` first, then `class`.

> [!NOTE]
> This works on plain HTML tags. On a component tag (`<x-icon class="..." @attributes>`) the `class` goes to that component the usual Blade way, so use `@base` there.

## Class maps

A class map is a table: the key is a value of the option, the item is its classes.

```blade
@props(['size' => 'md'])

@size([
    'sm' => 'h-8 px-3 text-sm',   // when size is "sm"
    'md' => 'h-10 px-4',          // when size is "md"
    'lg' => 'h-12 px-6 text-lg',  // when size is "lg"
])
```

```blade
<x-button size="sm">Cancel</x-button>   {{-- gets "h-8 px-3 text-sm" --}}
<x-button>Save</x-button>               {{-- no size given: the @props default "md" --}}
```

What if the value isn't in the table?

- `size="xl"` with no `'xl'` key: nothing is added. No error - unless you turn on [strict mode](10-debugging.md#strict-mode), which is a good idea in development.
- `null` means "not set": nothing is added.

The classes can be written three ways:

```blade
@color([
    'red' => 'bg-red-100 text-red-700',                         // a string
    'green' => ['bg-green-100', 'text-green-700'],              // a list
    'blue' => ['bg-blue-100 text-blue-700', 'animate-pulse' => $live],   // conditional, like @class
])
```

> [!TIP]
> Always write **full class names**: `'red' => 'bg-red-500'`, never `'bg-'.$color.'-500'`. Tailwind finds classes by reading your files, and it can't read a class that is glued together at runtime.

## `@variant`, `@color`, `@size`

These three come built in. **They work exactly the same way** - only the name differs, and the name is the prop they read:

| Directive | Reads | Typical values |
|---|---|---|
| `@variant([...])` | `$variant` | `primary`, `outline`, `ghost` |
| `@color([...])` | `$color` | `gray`, `red`, `green` |
| `@size([...])` | `$size` | `sm`, `md`, `lg` |

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
])

<button @attributes>{{ $slot }}</button>
```

```blade
<x-button variant="outline" size="sm">Cancel</x-button>
```

```html
<button class="inline-flex items-center justify-center rounded-lg font-medium border border-gray-300 hover:bg-gray-50 h-8 px-3 text-sm">Cancel</button>
```

Classes are added in the order the directives are written. Duplicates are removed.

## `@option` - any name you like

`@variant`, `@color` and `@size` are shortcuts. Each of them is `@option` with the name filled in:

```blade
@variant(['primary' => '...'])
{{-- is exactly the same as --}}
@option('variant', ['primary' => '...'])
```

So `@option` gives you an option with **any name**, right in the component:

```blade
@props(['radius' => 'md'])

@option('radius', [
    'none' => 'rounded-none',
    'md' => 'rounded-md',
    'full' => 'rounded-full',
])
```

```blade
<x-card radius="full" />
```

Use `@option` for options that only one or two components have (`radius`, `padding`, `orientation`). If an option shows up everywhere, [register it in the config](13-configuration.md#your-own-options) and you get a short `@radius([...])`.

## Where the value comes from

For each option Malevich takes the **first** value it finds:

1. An explicit call in the [`$attributes` API](14-attributes-api.md): `$attributes->size('sm')`.
2. A template variable with the same name - normally your `@props`, including its default.
3. An attribute with the same name on the tag, when the option isn't in `@props`: `<x-button size="sm">`.

In practice:

- **The default of an option is its `@props` default.** That's all there is to defaults.
- An option doesn't have to be in `@props` - the tag attribute is read directly. Add it to `@props` when you want a default or need `$size` in the markup.
- Option names are never printed in the HTML. You won't get `<button size="sm">`.

## Booleans

`true` and `false` are looked up as the keys `'true'` and `'false'`:

```blade
@props(['checked' => false])

@base('h-6 w-11 rounded-full')

@option('checked', [
    'true' => 'bg-green-500',
    'false' => 'bg-gray-300',
])

<span @attributes></span>
```

```blade
<x-switch />                           {{-- bg-gray-300 --}}
<x-switch checked />                   {{-- bg-green-500 --}}
<x-switch :checked="$user->active" />
```

A prop with a boolean default in `@props` also gets a real boolean in the template: `checked="false"` on the tag is the string `"false"`, and Malevich turns it into `false`, so `@if ($checked)` agrees with the class map. Only the exact strings `"true"` and `"false"` are converted; props with any other default are left as they came.

## Enums and numbers

A backed enum is looked up by its value, a plain enum by its case name. Numbers become strings: `:level="2"` looks up `'2'`.

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

## The `'*'` key

Inside a class map, `'*'` means "whatever the value". `@size(['*' => 'inline-flex', 'sm' => '...'])` is the same as `@base('inline-flex')` plus `@size(['sm' => '...'])`. It works, but `@base` is clearer.

---

Next: [Combinations](03-combinations.md)
