{{--
    <x-cat::chip variant="outline">
        New
    </x-cat::chip>
--}}

@props(['variant' => 'solid', 'size' => 'md'])

@variant(['solid' => 'chip-solid', 'outline' => 'chip-outline'])
@size(['sm' => 'chip-sm', 'md' => 'chip-md'])

<span @attributes>{{ $slot }}</span>
