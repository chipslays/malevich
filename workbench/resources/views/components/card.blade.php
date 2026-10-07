{{--
    <x-card preset="featured">...</x-card>
--}}

@props([
    'preset' => null,
    'variant' => null,
    'padding' => null,
])

@preset('featured', ['variant' => 'elevated', 'padding' => 'lg'])
@preset('compact', ['variant' => 'flat', 'padding' => 'sm'])

@base('rounded-2xl bg-white')

@variant([
    'flat' => 'border border-zinc-200',
    'elevated' => 'shadow-xl shadow-zinc-200 ring-1 ring-zinc-100',
])

@directive('padding', [
    'sm' => 'p-3',
    'md' => 'p-6',
    'lg' => 'p-10',
])

<div @ui>{{ $slot }}</div>
