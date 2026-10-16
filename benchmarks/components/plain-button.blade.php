@props(['variant' => 'primary', 'size' => 'md'])

<button {{ $attributes->class([
    'inline-flex items-center rounded-lg font-medium',
    'bg-black text-white' => $variant === 'primary',
    'border border-gray-300' => $variant === 'outline',
    'hover:bg-gray-100' => $variant === 'ghost',
    'h-8 px-3 text-sm' => $size === 'sm',
    'h-10 px-4' => $size === 'md',
    'h-12 px-6' => $size === 'lg',
    'shadow-lg' => $variant === 'primary' && $size === 'lg',
]) }}><i @class([
    'size-3' => $size === 'sm',
    'size-4' => $size === 'md',
    'size-5' => $size === 'lg',
])></i>{{ $slot }}</button>
