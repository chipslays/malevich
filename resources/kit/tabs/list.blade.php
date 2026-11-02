{{-- <x-tabs.list aria-label="Sections">Tab buttons go here.</x-tabs.list> --}}

@base('list', 'flex shrink-0 items-center gap-1')

<div @attributes('list', slot: $attributes, merge: ['role' => 'tablist', 'data-tabs-list' => ''])>{{ $slot }}</div>
