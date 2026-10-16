{{--
    <x-card variant="elevated" padding="lg">...</x-card>
--}}

@props([
    'variant' => 'flat',
    'padding' => 'md',
])

@base('rounded-2xl bg-white')

@variant([
    'flat' => 'border border-zinc-200',
    'elevated' => 'shadow-xl shadow-zinc-200 ring-1 ring-zinc-100',
])

@option('padding', [
    'sm' => 'p-3',
    'md' => 'p-6',
    'lg' => 'p-10',
])

<div @attributes>{{ $slot }}</div>
