{{--
    <x-alert color="red">
        <x-slot:title>Payment failed</x-slot:title>
        Your card was declined.
    </x-alert>
--}}

@props([
    'color' => 'blue',
    'title' => null,
])

@base('flex gap-3 rounded-xl border p-4 text-sm')

@color([
    'blue' => 'border-blue-200 bg-blue-50 text-blue-900',
    'green' => 'border-green-200 bg-green-50 text-green-900',
    'red' => 'border-red-200 bg-red-50 text-red-900',
])

@base('icon', 'mt-0.5 size-4 shrink-0')

@color('icon', [
    'blue' => 'text-blue-500',
    'green' => 'text-green-500',
    'red' => 'text-red-500',
])

@base('title', 'mb-1 font-semibold')

<div @ui(merge: ['role' => 'alert'])>
    <svg @ui('icon') viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd" />
    </svg>

    <div>
        @if ($title)
            <p @ui('title')>{{ $title }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
