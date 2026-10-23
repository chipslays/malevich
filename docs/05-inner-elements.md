# Inner elements

A component can style its root and its inner elements independently. Malevich calls each styled element a **target**. The root target is named `default`; inner targets have names such as `title` or `spinner`.

Use slot attributes when supplying content through a named slot. Use `target:option` when configuring an element that the component renders internally.

## Slot attributes or targeted attributes?

Choose based on where the content comes from:

| Situation | Prefer | Reason |
| --- | --- | --- |
| You already provide a named slot | `<x-slot:title size="lg" class="font-bold">` | Keep the content and its settings together. |
| The component renders the element itself, such as a loading spinner | `<x-save-button spinner:size="lg" loading>` | Configure the built-in element without supplying its markup. |
| You intentionally supply target settings separately from slot content | `<x-card title:size="lg">` | Set the target's defaults on the component; the slot can override them. |

For the card below, both forms work. When you write the title slot yourself, prefer putting `size`, `class` and `id` directly on that slot:

```blade
<x-card size="sm" aria-labelledby="billing-title">
    <x-slot:title size="lg" class="font-bold" id="billing-title">Billing</x-slot:title>
    Your next payment is due tomorrow.
</x-card>
```

You could also write this, but it separates the title from its settings:

```blade
<x-card size="sm" title:size="lg" title:class="font-bold" title:id="billing-title" aria-labelledby="billing-title">
    <x-slot:title>Billing</x-slot:title>
    Your next payment is due tomorrow.
</x-card>
```

Here `aria-labelledby` belongs on the card because it labels the whole section; `id` belongs on the title that supplies that label. Targeted attributes do not create a missing slot or make a conditional element render. They configure an element the component already renders.

For an internal spinner, `spinner:size="lg"` is useful because there is no caller-supplied slot to configure. The same applies to `icon:size="lg"` when a component creates its own icon. If you supply the icon through `<x-slot:icon>`, prefer `<x-slot:icon size="lg">` instead, provided the component connects that slot to its icon target.

If both forms specify the same option or HTML attribute, the slot's value wins. See [Which value wins?](#which-value-wins) for the complete precedence rules.

## Named slots: configure the content you provide

Here is a complete card with a title slot. Both elements respond to `size`, using their own class maps:

```blade
{{-- card.blade.php --}}
@props(['size' => 'sm'])

@base('rounded-lg border')
@base('title', 'font-semibold')

@size([
    'sm' => 'p-3 text-sm',
    'lg' => 'p-6 text-base',
])

@size('title', [
    'sm' => 'text-base',
    'lg' => 'text-2xl',
])

<section @attributes>
    @isset($title)
        <h2 @attributes('title')>{{ $title }}</h2>
    @endisset
    {{ $slot }}
</section>
```

Set the title's option directly on its slot:

```blade
<x-card size="sm">
    <x-slot:title size="lg" id="billing-title">Billing</x-slot:title>
    Your next payment is due tomorrow.
</x-card>
```

The card uses `p-3 text-sm`; the title uses `font-semibold text-2xl`. Its `id` is forwarded to `<h2>`, while `size` selects classes and is consumed. Without `size` on the slot, the title inherits the card's `sm`.

Bound options and ordinary HTML attributes work too:

```blade
<x-card>
    <x-slot:title :size="$titleSize" class="text-indigo-700" id="billing-title">
        Billing
    </x-slot:title>
    Your next payment is due tomorrow.
</x-card>
```

The slot name must match the target name for automatic wiring. If they differ, choose the slot explicitly: `@attributes('heading', slot: $title)`. Use `slot: false` to ignore slot attributes and options. A plain string prop has no slot attributes.

## Internal elements: configure them from the component tag

A progress indicator belongs to the button itself. Callers should not need to supply its SVG just to change its size:

```blade
{{-- save-button.blade.php --}}
@props(['size' => 'sm', 'loading' => false])

@base('inline-flex items-center gap-2 rounded-md')
@base('spinner', 'animate-spin motion-reduce:animate-none')

@size([
    'sm' => 'h-8 px-3 text-sm',
    'lg' => 'h-12 px-5 text-base',
])

@size('spinner', [
    'sm' => 'size-3',
    'lg' => 'size-5',
])

<button @attributes(merge: ['type' => 'button', 'disabled' => $loading])>
    @if ($loading)
        <svg @attributes('spinner', merge: ['aria-hidden' => 'true']) viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2" />
            <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" />
        </svg>
    @endif
    {{ $slot }}
</button>
```

```blade
{{-- Small button and small spinner. --}}
<x-save-button size="sm" loading>Saving</x-save-button>

{{-- Small button with a larger spinner. --}}
<x-save-button size="sm" spinner:size="lg" loading>Saving</x-save-button>

{{-- Bind the spinner size or add a class to its SVG. --}}
<x-save-button :spinner:size="$spinnerSize" spinner:class="opacity-70" loading>
    Saving
</x-save-button>
```

`spinner:size` affects only the spinner, including compound conditions declared for that target. The root and other targets keep the shared `size`. Ordinary root attributes such as `id` and `class` stay on the root.

Declare each target through `@base`, an option map or a compound rule. Malevich then consumes its `target:*` attributes on the root and forwards ordinary attributes to that target. Namespaces are never reserved: attributes belonging to neither a declared target nor a declared camel-case prop pass through unchanged, including their modifiers. This applies equally to Alpine, Livewire and custom integrations.

For example, a component with an `icon` target consumes `icon:size`, but forwards `wire:click`, `x-on:click` and `analytics:track.once` to its root. Declaring a target named `wire` or a prop named `wireClick` explicitly gives the component ownership of that input. Choose distinct names when an external integration uses the same attribute; identical names cannot express two different intentions.

Per-target arrays remain supported:

```blade
<x-save-button :size="['default' => 'lg', 'spinner' => 'sm']" loading>
    Saving
</x-save-button>
```

## Which value wins?

Option values use the following priority, taking the first non-null value:

1. An explicit fluent choice, such as `->size('lg')`.
2. The named slot's option, such as `<x-slot:title size="lg">`.
3. A targeted attribute, such as `title:size="lg"`.
4. The shared component option, including its `@props` default.
5. An ordinary attribute on the component when no template variable supplies the option.

Boolean `false` is a value, not a request to fall back. Slot options work with compound conditions and strict mode. Debug output identifies choices taken from slots.

For ordinary HTML attributes, named-slot attributes also take precedence over targeted attributes. Slot classes are appended after declared classes; conflicting Tailwind utilities require the same merge integration described in [Tailwind](07-tailwind.md#class-conflicts).

## Scoped inputs as declared Blade props

Declare a camel-case prop when the component needs a scoped value as a template variable:

```blade
{{-- Inside the component: --}}
@props(['iconName' => null])
```

```blade
{{-- At the call site: --}}
<x-symbol icon:name="check" />
```

For this custom component, Malevich maps `icon:name` to `$iconName`. The scoped input is consumed and does not leak into HTML. Bound input (`:icon:name="$name"`), booleans, empty strings, ordinary camel-case input and Blade's kebab-case aliases remain supported. When both forms are passed, scoped input takes precedence over its ordinary alias. Without a matching declared prop, scoped attributes continue to configure targets as described above. Use a named slot instead of a scalar prop when the value should contain HTML or Blade components.

Modifiers do not change the prop's name or its value:

```blade
{{-- Inside a component: --}}
@props(['iconTrailing' => null])

@php
    // For icon:trailing.filled[false].size[24].fallback[]="heart":
    // $iconTrailing is 'heart'.
    $settings = $attributes->for('icon')->modifiers('trailing');
    // ['filled' => false, 'size' => '24', 'fallback' => null]
@endphp
```

Metadata remains available after Blade consumes the prop. The existing target API can also read its value with `$attributes->for('icon')->get('trailing')`. Modifier values are metadata, not executable expressions.

## Modifiers: extra settings for one value

Modifiers are useful when a component accepts a value with a few related settings. For example, a text component can accept a caption with an optional length limit and uppercase conversion:

```blade
<x-caption text:value.uppercase.limit[12]="Account settings" />
```

Here is the complete component implementing those settings:

```blade
{{-- caption.blade.php --}}
@props(['textValue' => ''])

@base('text', 'font-medium')

@php
    $text = $attributes->for('text');
    $value = (string) $textValue;
    $settings = $text->modifiers('value');

    if ($settings['uppercase'] ?? false) {
        $value = mb_strtoupper($value);
    }

    $limit = $settings['limit'] ?? null;

    if ($limit !== null && ctype_digit((string) $limit) && (int) $limit > 0) {
        $value = mb_strimwidth($value, 0, (int) $limit, '…');
    }
@endphp

<span @attributes>{{ $value }}</span>
```

The scoped value is consumed by the declared `textValue` prop. Its modifier settings remain accessible through the target API. This component renders only its processed value; it does not print `@attributes('text')`.

```blade
{{-- Uppercase, at most 12 display-width units including the ellipsis. --}}
<x-caption text:value.uppercase.limit[12]="Account settings" />

{{-- Explicitly disable uppercase and remove the limit. --}}
<x-caption text:value.uppercase[false].limit[]="Account settings" />

{{-- Bind the caption value; modifier arguments remain literal. --}}
<x-caption :text:value.limit[24]="$caption" />
```

Malevich parses settings; each component decides what those settings do. Modifiers never execute PHP or JavaScript or change classes automatically.

| Syntax | Modifier value |
| --- | --- |
| `.uppercase` | `true` |
| `.uppercase[true]` | `true` |
| `.uppercase[false]` | `false` |
| `.limit[24]` | string `'24'` |
| `.format[short]` | string `'short'` |
| `.limit[]` | `null`, with the key still present |

Write string arguments without quotes. Only lowercase `true` and `false` become booleans; `[True]` and `[null]` remain strings. Arguments cannot contain whitespace or nested brackets. For repeated modifiers or multiple attributes addressing the same target and prop, the last occurrence wins. Prefer one attribute per prop so its value and settings stay together.

## Parts that exist only in some variants

`@has('name') ... @endhas` renders a part only when its target has classes:

```blade
@props(['variant' => 'graphite'])

@cases('variant', [
    'teal' => ['default' => 'bg-teal-600', 'glow' => 'absolute blur-3xl bg-teal-300/30'],
    'graphite' => ['default' => 'bg-gray-900'],
])

<div @attributes>
    @has('glow')
        <div @attributes('glow')></div>
    @endhas
</div>
```

`variant="teal"` draws the glow; `variant="graphite"` skips it. Keep all optional-part classes in the variants: adding `@base('glow', ...)` would make the target present in every variant. `@else` works, and `@has` accepts the same arguments as `@attributes`.

---

Next: [Building components](06-building-components.md)
