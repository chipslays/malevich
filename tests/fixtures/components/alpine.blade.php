@props(['variant' => 'a'])

@variant(['a' => 'v-a'])
@base('icon', 'ic')

<x-malevich::primitive as="span" x-show="count > 0" @ui>
    <x-malevich::primitive as="i" @ui("icon", merge: ['title' => 'a)b']) />
</x-malevich::primitive>
