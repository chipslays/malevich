{{-- <x-tabs.panel value="overview">Project overview.</x-tabs.panel> --}}

@props(['value'])

@base('panel', 'min-w-0 flex-1 rounded-md text-sm leading-6 text-ink outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus')

<section @attributes('panel', slot: $attributes, merge: ['role' => 'tabpanel', 'data-value' => $value, 'tabindex' => '0'])>{{ $slot }}</section>
