{{-- <x-heading as="h1" size="xl">Workspace settings</x-heading> --}}

@props(['as' => 'h2', 'size' => 'md', 'color' => 'contrast'])

@base('heading', 'text-balance font-semibold tracking-tight text-(--tone-ink)')
@cases('color', [
    'brand' => ['heading' => ['tone-brand']],
    'neutral' => ['heading' => ['tone-neutral']],
    'contrast' => ['heading' => ['tone-contrast']],
    'success' => ['heading' => ['tone-success']],
    'info' => ['heading' => ['tone-info']],
    'warning' => ['heading' => ['tone-warning']],
    'danger' => ['heading' => ['tone-danger']],
])
@size('heading', [
    'xs' => 'text-base leading-6',
    'sm' => 'text-lg leading-7',
    'md' => 'text-xl leading-7',
    'lg' => 'text-2xl leading-8',
    'xl' => 'text-[1.75rem] leading-9',
    '2xl' => 'text-4xl leading-[1.2]',
])

<x-malevich::primitive :as="$as" @attributes('heading', slot: $attributes)>{{ $slot }}</x-malevich::primitive>
