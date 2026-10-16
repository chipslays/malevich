@props(['variant' => 'a'])

@variant(['a' => 'v-a'])
@base('icon', 'ic')

<x-malevich::primitive as="span" x-show="count > 0" @attributes>
    <x-malevich::primitive as="i" @attributes("icon", merge: ['title' => 'a)b']) />
</x-malevich::primitive>
