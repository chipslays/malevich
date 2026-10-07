@props(['variant' => 'outline', 'color' => 'primary', 'size' => 'md'])

@variant([
    '*' => 'inline-flex',
    'solid' => 'font-bold',
    'outline' => 'border',
])

@color([
    'primary' => 'text-blue-500',
    'danger' => 'text-red-500',
])

@size([
    'sm' => 'text-sm',
    'md' => 'text-base',
])

@compound(['variant' => 'solid', 'color' => 'danger'], 'bg-red-500 text-white')

<span @ui>{{ $slot }}</span>
