{{--
    <x-button variant="outline" size="sm" loading>Save</x-button>
--}}

@props([
    'as' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
])

@base('inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 data-disabled:pointer-events-none data-disabled:opacity-50')

@variant([
    'primary' => 'bg-zinc-900 text-white hover:bg-zinc-700',
    'secondary' => 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200',
    'outline' => 'border border-zinc-300 bg-white hover:bg-zinc-50',
    'ghost' => 'hover:bg-zinc-100',
    'danger' => 'bg-red-600 text-white hover:bg-red-500',
])

@size([
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
])

{{-- A second element inside the button: the spinner. Same "size" prop, different classes. --}}
@base('spinner', 'animate-spin')

@size('spinner', [
    'sm' => 'size-3.5',
    'md' => 'size-4',
    'lg' => 'size-5',
])

<x-malevich::primitive :as="$as" @ui>
    @if ($loading)
        <svg @ui('spinner') viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
        </svg>
    @endif

    {{ $slot }}
</x-malevich::primitive>
