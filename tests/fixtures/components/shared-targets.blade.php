@props(['size' => 'sm'])

@cases('size', [
    'sm' => ['default' => 'root-sm', 'a,b' => 'shared-sm', 'b' => 'only-b'],
    'lg' => ['default' => 'root-lg', 'a, b' => 'shared-lg'],
])
@compound(['size' => 'sm'], targets: ['a,b' => 'both-compound'])

<div @attributes><i @attributes('a')></i><u @attributes('b')></u></div>
