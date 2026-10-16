{{--
    <x-cat::card>
        <x-slot:title>Billing</x-slot:title>
        Your plan renews soon.
    </x-cat::card>
--}}

@props(['title' => null])

@base('card')

<div @attributes>@if ($title)<h3>{{ $title }}</h3>@endif{{ $slot }}</div>
