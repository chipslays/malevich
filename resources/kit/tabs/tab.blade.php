{{-- <x-tabs.tab value="overview">Overview</x-tabs.tab> --}}

@props(['value', 'disabled' => false])

@base('tab', 'relative inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium text-ink-muted transition-colors duration-150 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:pointer-events-none disabled:opacity-40 motion-reduce:transition-none')

<button @attributes('tab', slot: $attributes, merge: ['type' => 'button', 'role' => 'tab', 'data-value' => $value, 'aria-selected' => 'false', 'disabled' => $disabled])>{{ $slot }}</button>
