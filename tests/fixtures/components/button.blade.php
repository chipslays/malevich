@props(['icon' => null])

@variant([
    '*' => 'btn',
    'solid' => 'btn-solid',
    'ghost' => 'btn-ghost',
])

@size('icon', [
    'sm' => 'w-3',
    'lg' => 'w-6',
])

<button @attributes(merge: ['type' => 'button'])>
    @if ($icon)<svg @attributes('icon')></svg>@endif
    {{ $slot }}
</button>
