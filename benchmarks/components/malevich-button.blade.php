@props(['variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center rounded-lg font-medium')

@variant([
    'primary' => 'bg-black text-white',
    'outline' => 'border border-gray-300',
    'ghost' => 'hover:bg-gray-100',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6',
])

@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')

@size('icon', [
    'sm' => 'size-3',
    'md' => 'size-4',
    'lg' => 'size-5',
])

<button @attributes><i @attributes('icon')></i>{{ $slot }}</button>
