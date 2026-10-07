{{--
    <x-badge color="green">Paid</x-badge>
    <x-badge variant="solid" color="red">Overdue</x-badge>
--}}

@props([
    'variant' => 'soft',
    'color' => 'gray',
])

@base('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')

@variant([
    'soft' => '',
    'solid' => 'text-white!',
    'outline' => 'border bg-transparent',
])

@color([
    'gray' => 'text-zinc-700',
    'green' => 'text-green-700',
    'red' => 'text-red-700',
    'blue' => 'text-blue-700',
])

{{-- Background depends on BOTH variant and color: that's what @compound is for. --}}
@compound(['variant' => 'soft', 'color' => 'gray'], 'bg-zinc-100')
@compound(['variant' => 'soft', 'color' => 'green'], 'bg-green-100')
@compound(['variant' => 'soft', 'color' => 'red'], 'bg-red-100')
@compound(['variant' => 'soft', 'color' => 'blue'], 'bg-blue-100')

@compound(['variant' => 'solid', 'color' => 'gray'], 'bg-zinc-700')
@compound(['variant' => 'solid', 'color' => 'green'], 'bg-green-600')
@compound(['variant' => 'solid', 'color' => 'red'], 'bg-red-600')
@compound(['variant' => 'solid', 'color' => 'blue'], 'bg-blue-600')

@compound(['variant' => 'outline', 'color' => 'gray'], 'border-zinc-300')
@compound(['variant' => 'outline', 'color' => 'green'], 'border-green-300')
@compound(['variant' => 'outline', 'color' => 'red'], 'border-red-300')
@compound(['variant' => 'outline', 'color' => 'blue'], 'border-blue-300')

<span @ui>{{ $slot }}</span>
