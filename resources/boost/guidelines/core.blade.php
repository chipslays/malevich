## Malevich

Malevich (`malevich/malevich`) builds variant-driven Blade components: you declare class maps per option, and `@ui` prints the final `class` and attributes. Use it for every reusable Blade component that has options (variant, color, size, flags) instead of `$attributes->class([... => $variant === 'x'])` ternaries.

- Components are plain Blade files (`resources/views/components/...`). Scaffold one with `php artisan make:malevich button`.
- Declare directives (`@base`, `@variant`, `@color`, `@size`, `@directive`, `@theme`, `@compound`, `@preset`) right after `@props` and **before** `@ui`.
- When one option restyles several inner elements, `@theme('variant', [value => [target => classes]])` groups the maps by value; `@hasUi('target') ... @endif` draws a part only when it has classes.
- Put always-on classes in `@base('...')`, never in a `class` attribute on the `@ui` element.
- Write full literal Tailwind class names in the maps; never build class names from variables.
- Activate the `malevich-development` skill when creating or editing such components.

@verbatim
<code-snippet name="Malevich component" lang="blade">
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

@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')

<button @ui(merge: ['type' => 'button'])>{{ $slot }}</button>
</code-snippet>
@endverbatim
