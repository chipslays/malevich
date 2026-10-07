{{--
    Unstyled base element. Build your own components on top of it:

    <x-malevich::primitive as="button" @ui>{{ $slot }}</x-malevich::primitive>
--}}

@props([
    'as' => null,
    'disabled' => false,
])

@php($primitive = new \Malevich\Primitive($attributes, $as, (bool) $disabled))

{{ $primitive->open() }}@unless ($primitive->isVoid()){{ $slot }}@endunless{{ $primitive->close() }}
