@props(['size' => 'md', 'variant' => 'solid', 'muted' => false])

@base('btn')
@base('icon', 'icon')

@size(['sm' => 'h-8', 'md' => 'h-10'])
@variant(['solid' => 'bg-black'])

@compound(['size' => 'sm', 'variant' => 'ghost'], targets: [
    'default' => 'px-2',
    'icon' => ['size-3', 'opacity-70' => $muted],
    'label' => 'text-xs',
])

<button @attributes><i @attributes('icon')></i><span @attributes('label')>{{ $slot }}</span></button>
