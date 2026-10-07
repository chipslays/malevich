@props(['variant' => 'primary', 'as' => 'button'])

@base('btn')
@variant(['primary' => 'btn-primary', 'ghost' => 'btn-ghost'])
@base('icon', 'icon')

<x-malevich::primitive :as="$as" @ui>
    <x-malevich::primitive as="span" @ui('icon') />{{ $slot }}
</x-malevich::primitive>
