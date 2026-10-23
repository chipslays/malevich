# Combinations

A class map answers "which classes for **this one value**?". Sometimes a class depends on **two options at once**, or several elements change together. This chapter covers `@compound` and `@cases`, and ends with a table that tells you which tool to pick.

## `@compound` - classes for a combination

A badge has `variant` (`soft` / `solid`) and `color` (`gray` / `red`). The background depends on **both**:

| | gray | red |
|---|---|---|
| **soft** | `bg-gray-100` | `bg-red-100` |
| **solid** | `bg-gray-700` | `bg-red-600` |

`bg-red-100` can't go into `@color` (it would apply to solid too) or into `@variant` (it would apply to gray too). That's what `@compound` is for:

```blade
@props(['variant' => 'soft', 'color' => 'gray'])

@base('rounded-full px-2 text-xs')

@variant(['solid' => 'text-white'])

@color([
    'gray' => 'text-gray-700',
    'red' => 'text-red-700',
])

@compound(['variant' => 'soft', 'color' => 'gray'], 'bg-gray-100')
@compound(['variant' => 'soft', 'color' => 'red'], 'bg-red-100')
@compound(['variant' => 'solid', 'color' => 'gray'], 'bg-gray-700')
@compound(['variant' => 'solid', 'color' => 'red'], 'bg-red-600')

<span @attributes>{{ $slot }}</span>
```

Read each line as a sentence: *"if variant is soft **and** color is red, add `bg-red-100`"*.

```blade
<x-badge variant="solid" color="red">Overdue</x-badge>
```

```html
<span class="rounded-full px-2 text-xs text-white text-red-700 bg-red-600">Overdue</span>
```

### The rules

- **All** conditions must match.
- A list means **"any of these"**:

  ```blade
  @compound(['variant' => ['outline', 'ghost'], 'color' => 'red'], 'hover:bg-red-50')
  ```

- Classes can be a string, a list or a conditional array, like in a class map.
- Compound classes come **after** all class maps.

### Reacting to a flag

`@compound` also works with props that have no class map. That makes it the easiest way to react to a `true`/`false` prop:

```blade
@props(['size' => 'md', 'loading' => false])

@compound(['loading' => true], 'cursor-wait opacity-75')
@compound(['loading' => true, 'size' => 'sm'], 'gap-1')
```

### For inner elements

Put the element's name first:

```blade
@compound('icon', ['variant' => 'ghost'], 'opacity-70')
```

When one combination restyles several elements, write the conditions once and list the classes per element with `targets:`. `default` is the main element:

```blade
@compound(['size' => 'sm', 'variant' => 'ghost'], targets: [
    'default' => 'px-2',
    'icon' => 'size-3',
    'label' => 'text-xs',
])
```

## `@cases` - group by value instead of by element

When a component has several elements and **every value restyles most of them**, writing one `@variant('element', [...])` per element gets scattered. `@cases` groups the same classes **by value**:

```blade
@props(['variant' => 'teal'])

@base('flex rounded-xl p-6')
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

<div @attributes>
    <svg @attributes('icon')></svg>
    <h2 @attributes('title')>{{ $title }}</h2>
</div>
```

Read it as: *"when variant is teal, the card is `bg-teal-600`, the title is `text-teal-100` and the icon is `text-teal-200`"*.

- The first argument is the option: `@cases('size', [...])`, `@cases('color', [...])` or any other name.
- The inner keys are elements; `default` is the main one.
- A value can also be plain classes - they style the main element: `'teal' => 'bg-teal-600'`.
- Write conditional classes under an element key, never directly as the value.
- It is exactly the same as one `@variant('element', [...])` per element, so everything else (`@compound`, [named slots](05-inner-elements.md#named-slots)) works as usual. Pick one style per option and stick to it.

## Which tool should I use?

| I want to... | Use |
|---|---|
| Classes that are always there | `@base('...')` or `class="..."` next to `@attributes` |
| Classes that depend on one prop | `@variant` / `@color` / `@size`, or `@option('name', [...])` |
| Classes for an inner element | the same directives with the element's name first: `@size('icon', [...])` |
| Classes when two props have certain values | `@compound` |
| React to a flag (`loading`, `active`) | `@compound(['loading' => true], '...')` or a [`'true'`/`'false'` map](02-classes.md#booleans) |
| Many elements change together per value | `@cases` |
| An element that exists only in some variants | [`@styled`](05-inner-elements.md#parts-that-exist-only-in-some-variants) |
| A default value | the `@props` default |
| Default HTML attributes (`type`, `role`, `aria-*`) | [`@attributes(merge: [...])`](04-attributes.md#default-attributes-merge) |
| Users add classes to an inner element | a [named slot](05-inner-elements.md#named-slots) |
| Build on another component | [`<x-other @attributes />`](06-building-components.md) |
| The same option in many components | [register it in the config](13-configuration.md#your-own-options) |
| The same class maps in many components | an [`@include` partial](06-building-components.md#sharing-styles-between-components) |

---

Next: [`@attributes` in detail](04-attributes.md)
