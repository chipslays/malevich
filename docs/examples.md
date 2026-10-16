# Examples

Complete components to copy and adapt. Each one shows a few features at once; the comments point to the chapter that explains them. More live in [`workbench/resources/views/components`](../workbench/resources/views/components) - run `composer serve` in the repository to see them in a browser.

## Button

Variants, sizes, a combination, a loading spinner and default attributes.

```blade
{{-- resources/views/components/button.blade.php --}}
{{--
    <x-button variant="outline" size="sm">Cancel</x-button>
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
])

@base('inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors disabled:opacity-50')

@variant([
    'primary' => 'bg-zinc-900 text-white hover:bg-zinc-700',
    'outline' => 'border border-zinc-300 bg-white hover:bg-zinc-50',
    'danger' => 'bg-red-600 text-white hover:bg-red-500',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])

{{-- the spinner: an inner element (05-inner-elements.md) --}}
@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3.5',
    'md' => 'size-4',
    'lg' => 'size-5',
])

{{-- combinations and flags (03-combinations.md) --}}
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')
@compound(['loading' => true], 'cursor-wait')

<button @attributes(merge: ['type' => 'button'])>
    @if ($loading)
        <svg @attributes('spinner') viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" />
        </svg>
    @endif

    {{ $slot }}
</button>
```

```blade
<x-button>Save</x-button>
<x-button variant="outline" size="sm">Cancel</x-button>
<x-button variant="danger" loading wire:click="delete">Deleting</x-button>
<x-button size="lg" type="submit" class="w-full">Sign up</x-button>
```

Want it to become a link with `href` and handle `disabled` properly? Build it on the [primitive](06-building-components.md#the-unstyled-primitive).

## Badge

Two options whose classes depend on each other - a job for `@compound`.

```blade
{{-- resources/views/components/badge.blade.php --}}
{{--
    <x-badge variant="solid" color="red">Overdue</x-badge>
--}}

@props([
    'variant' => 'soft',
    'color' => 'gray',
])

@base('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')

@color([
    'gray' => 'text-zinc-700',
    'green' => 'text-green-700',
    'red' => 'text-red-700',
])

{{-- after @color, so "text-white" comes after the color's text class --}}
@variant([
    'solid' => 'text-white',
    'outline' => 'border bg-transparent',
])

{{-- the background depends on BOTH variant and color --}}
@compound(['variant' => 'soft', 'color' => 'gray'], 'bg-zinc-100')
@compound(['variant' => 'soft', 'color' => 'green'], 'bg-green-100')
@compound(['variant' => 'soft', 'color' => 'red'], 'bg-red-100')

@compound(['variant' => 'solid', 'color' => 'gray'], 'bg-zinc-700')
@compound(['variant' => 'solid', 'color' => 'green'], 'bg-green-600')
@compound(['variant' => 'solid', 'color' => 'red'], 'bg-red-600')

@compound(['variant' => 'outline', 'color' => 'gray'], 'border-zinc-300')
@compound(['variant' => 'outline', 'color' => 'green'], 'border-green-300')
@compound(['variant' => 'outline', 'color' => 'red'], 'border-red-300')

<span @attributes>{{ $slot }}</span>
```

Class maps are applied in the order they are written, so `text-white` comes after the color's `text-*` class. With [tailwind-merge](07-tailwind.md#class-conflicts) installed, the later class wins and the other one is removed.

## Alert

An icon and a title styled per color, a title that users can style through a named slot, and a default `role`.

```blade
{{-- resources/views/components/alert.blade.php --}}
{{--
    <x-alert color="red">
        <x-slot:title>Payment failed</x-slot:title>
        Your card was declined.
    </x-alert>
--}}

@props([
    'color' => 'blue',
    'title' => null,
])

@base('flex gap-3 rounded-xl border p-4 text-sm')

@color([
    'blue' => 'border-blue-200 bg-blue-50 text-blue-900',
    'green' => 'border-green-200 bg-green-50 text-green-900',
    'red' => 'border-red-200 bg-red-50 text-red-900',
])

@base('icon', 'mt-0.5 size-4 shrink-0')

@color('icon', [
    'blue' => 'text-blue-500',
    'green' => 'text-green-500',
    'red' => 'text-red-500',
])

@base('title', 'mb-1 font-semibold')

<div @attributes(merge: ['role' => 'alert'])>
    <svg @attributes('icon') viewBox="0 0 20 20" fill="currentColor">...</svg>

    <div>
        @if ($title)
            <p @attributes('title')>{{ $title }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
```

```blade
<x-alert color="green">
    <x-slot:title class="text-base">Saved</x-slot:title>
    Your changes are live.
</x-alert>
```

## Card

An option that only this component has - no config needed with `@option`.

```blade
{{-- resources/views/components/card.blade.php --}}
{{--
    <x-card variant="elevated" padding="lg">...</x-card>
--}}

@props([
    'variant' => 'flat',
    'padding' => 'md',
])

@base('rounded-2xl bg-white')

@variant([
    'flat' => 'border border-zinc-200',
    'elevated' => 'shadow-xl ring-1 ring-zinc-100',
])

@option('padding', [
    'sm' => 'p-3',
    'md' => 'p-6',
    'lg' => 'p-10',
])

<div @attributes>{{ $slot }}</div>
```

## Switch

A boolean option, and an inner element (the thumb) that moves with it.

```blade
{{-- resources/views/components/switch.blade.php --}}
{{--
    <x-switch checked />
--}}

@props(['checked' => false])

@base('relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors')
@base('thumb', 'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform')

@option('checked', [
    'true' => 'bg-green-500',
    'false' => 'bg-zinc-300',
])

@option('checked', 'thumb', [
    'true' => 'translate-x-5',
])

<span @attributes(merge: ['role' => 'switch', 'aria-checked' => $checked ? 'true' : 'false'])>
    <span @attributes('thumb')></span>
</span>
```

```blade
<x-switch />
<x-switch checked />
<x-switch :checked="$user->notifications_enabled" />
```
