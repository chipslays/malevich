@props(['variant' => 'primary', 'as' => 'button'])

@base('btn')
@variant(['primary' => 'btn-primary', 'ghost' => 'btn-ghost'])
@base('icon', 'icon')

<x-malevich::primitive :as="$as" @attributes>
    <x-malevich::primitive as="span" @attributes('icon') />{{ $slot }}
</x-malevich::primitive>
