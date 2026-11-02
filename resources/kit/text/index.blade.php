{{-- <x-text color="neutral">A little context makes everything clearer.</x-text> --}}

@props(['as' => 'p', 'size' => 'sm', 'color' => 'contrast', 'weight' => 'normal', 'truncate' => false])

@base('text', ['text-(--tone-ink)', 'text-pretty' => !$truncate, 'truncate' => $truncate])
@cases('color', [
    'brand' => ['text' => ['tone-brand']],
    'neutral' => ['text' => ['tone-neutral']],
    'contrast' => ['text' => ['tone-contrast']],
    'success' => ['text' => ['tone-success']],
    'info' => ['text' => ['tone-info']],
    'warning' => ['text' => ['tone-warning']],
    'danger' => ['text' => ['tone-danger']],
])
@size('text', ['xs' => 'text-xs leading-5', 'sm' => 'text-sm leading-6', 'md' => 'text-base leading-7', 'lg' => 'text-lg leading-8'])
@option('weight', 'text', ['normal' => 'font-normal', 'medium' => 'font-medium', 'semibold' => 'font-semibold'])

<x-malevich::primitive :as="$as" @attributes('text', slot: $attributes)>{{ $slot }}</x-malevich::primitive>
