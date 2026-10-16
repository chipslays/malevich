@props(['size' => 'md'])

@base('btn')
@variant(['primary' => 'bg-black', 'danger' => 'bg-red'])
@size(['md' => 'h-10', 'lg' => 'h-12'])
@compound(['variant' => 'danger', 'size' => 'lg'], 'shadow')

<div x-data="{ classes: {{ Js::from($attributes->size($size)->classesFor('variant')) }} }"></div>
