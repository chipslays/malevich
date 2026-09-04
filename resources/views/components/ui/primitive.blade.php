{{--
    <x-ui::primitive>

    The base "polymorphic" building block every other component in this
    package is composed from. It renders as any HTML tag (`as`) and knows
    how to accept a class in two forms:

      1. A plain string -> used as-is.
      2. A Malevich\Selector -> resolved into a class string first. This is
         what lets other components pass `$attributes->for('track')->use(...)`
         straight into `class` instead of calling ->resolveClasses() themselves.

    Every other attribute (id, wire:model, x-data, ...) passes through untouched via $attributes.

    Usage:
        <x-ui::primitive as="button" :class="$attributes->for('handle')->use(['size' => $size])">
            {{ $slot }}
        </x-ui::primitive>

    Props:
        as - HTML tag to render (default: "div").
        class - string|Malevich\Selector|null. Anything else is ignored.
--}}
@props([
    'as' => 'div',
    'class' => null,
])

@php
    // `class` may be a plain string, or a Malevich\Selector produced by
    // $attributes->for()/->use()/->preset(); in the latter case we resolve
    // it to a class string before merging it back onto the attribute bag.
    $classes = match (true) {
        $class instanceof \Malevich\Selector => ['class' => $class->resolveClasses()],
        is_string($class) => ['class' => $class],
        default => [],
    };
@endphp

<{{ $as }} {{ $attributes->except(['class', 'as'])->merge($classes) }}>
    {{ $slot }}
</{{ $as }}>
