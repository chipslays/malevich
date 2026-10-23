{{--
    <x-cat::fresh>Fresh</x-cat::fresh>
--}}

@props(['variant' => null])

@base('fresh')

@variant([])

<div @attributes>{{ $slot }}</div>
