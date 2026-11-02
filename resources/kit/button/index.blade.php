{{-- <x-button>Continue</x-button> --}}

@props([
    'variant' => 'solid',
    'color' => 'brand',
    'size' => 'md',
    'radius' => 'brand',

    'block' => false,
    'square' => null,

    'loading' => false,
    'loadingLabel' => null,
    'disabled' => false,

    'leading' => null,
    'trailing' => null,

    'as' => 'button',
])

@php
    $square = $square === null ? trim(strip_tags((string) $slot)) === '' : filter_var($square, FILTER_VALIDATE_BOOL);
    $overlayLoading = $loading && ($square || $loadingLabel !== null);
    $hasLoadingLabel = $loadingLabel instanceof \Illuminate\View\ComponentSlot ? $loadingLabel->hasActualContent() : filled($loadingLabel);
@endphp

@base('button', [
    'button-colors',
    'relative inline-flex shrink-0 items-center justify-center align-middle',
    'border',
    'font-medium whitespace-nowrap select-none',
    'transition-colors duration-150 motion-reduce:transition-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--button-focus)',
    'data-disabled:pointer-events-none data-disabled:opacity-50 data-disabled:cursor-not-allowed',
    'active:translate-y-px',
    'aspect-square p-0' => $square,
])

@base('content', 'inline-flex min-w-0 items-center justify-center gap-1.5')
@base('leading', 'inline-flex shrink-0 items-center')
@base('trailing', 'inline-flex shrink-0 items-center')
@base('loading', 'absolute inset-0 inline-flex items-center justify-center gap-1.5')
@base('loadingLabel', 'min-w-0 truncate')
@base('spinner', 'inline-block shrink-0 animate-spin rounded-full border-2 border-current border-e-transparent motion-reduce:animate-none')
@cases('variant', [
    'solid' => [
        'button' => [
            'border-transparent',
            'bg-(--button-solid) text-(--button-on-solid)',
            'hover:bg-(--button-solid-hover) active:bg-(--button-solid-active)',
        ],
    ],
    'soft' => [
        'button' => [
            'border-transparent',
            'bg-(--button-soft) text-(--button-text)',
            'hover:bg-(--button-soft-hover) active:bg-(--button-soft-active)',
        ],
    ],
    'surface' => [
        'button' => [
            'bg-(--button-surface) text-(--button-text) border-(--button-border)',
            'hover:bg-(--button-soft) hover:border-(--button-border-hover) active:bg-(--button-soft-hover)',
        ],
    ],
    'elevated' => [
        'button' => [
            'shadow-xs text-(--button-text)',
            'bg-surface-raised border-line hover:bg-(--button-surface) active:bg-(--button-soft)',
        ],
    ],
    'outline' => [
        'button' => [
            'bg-transparent text-(--button-text) border-(--button-border)',
            'hover:bg-(--button-soft) hover:text-(--button-text) hover:border-(--button-border-hover)',
            'active:bg-(--button-soft-hover)',
        ],
    ],
    'ghost' => [
        'button' => [
            'border-transparent bg-transparent text-(--button-text)',
            'hover:bg-(--button-soft) hover:text-(--button-text) active:bg-(--button-soft-hover)',
        ],
    ],
    'link' => [
        'button' => [
            'border-transparent bg-transparent text-(--button-text)',
            'underline-offset-4 hover:underline hover:text-(--button-text-hover) active:text-(--button-text-active)',
        ],
    ],
])

@cases('color', [
    'brand' => ['button' => ['tone-brand']],
    'neutral' => ['button' => ['tone-neutral']],
    'contrast' => ['button' => ['tone-contrast']],
    'success' => ['button' => ['tone-success']],
    'info' => ['button' => ['tone-info']],
    'warning' => ['button' => ['tone-warning']],
    'danger' => ['button' => ['tone-danger']],
])

@size('button', [
    'xs' => ['text-xs', 'px-2 h-7' => !$square, 'size-7' => $square],
    'sm' => ['text-sm', 'px-3 h-8' => !$square, 'size-8' => $square],
    'md' => ['text-sm', 'px-3.5 h-9' => !$square, 'size-9' => $square],
    'lg' => ['text-base', 'px-4 h-10' => !$square, 'size-10' => $square],
    'xl' => ['text-base', 'px-5 h-11' => !$square, 'size-11' => $square],
])

@size('spinner', ['xs' => 'size-3', 'sm' => 'size-3.5', 'md' => 'size-4', 'lg' => 'size-4.5', 'xl' => 'size-5'])
@cases('size', [
    'xs' => ['leading' => ['[&>svg]:size-3'], 'trailing' => ['[&>svg]:size-3']],
    'sm' => ['leading' => ['[&>svg]:size-3.5'], 'trailing' => ['[&>svg]:size-3.5']],
    'md' => ['leading' => ['[&>svg]:size-4'], 'trailing' => ['[&>svg]:size-4']],
    'lg' => ['leading' => ['[&>svg]:size-4.5'], 'trailing' => ['[&>svg]:size-4.5']],
    'xl' => ['leading' => ['[&>svg]:size-5'], 'trailing' => ['[&>svg]:size-5']],
])

@option('radius', 'button', [
    'brand' => 'rounded-brand',
    'none' => 'rounded-none',
    'xs' => 'rounded-xs',
    'sm' => 'rounded-sm',
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    '2xl' => 'rounded-2xl',
    '3xl' => 'rounded-3xl',
    '4xl' => 'rounded-4xl',
    'full' => 'rounded-full',
    'squircle' => ['rounded-2xl', '[corner-shape:squircle]'],
])

@compound(['block' => true], targets: ['button' => ['w-full']])

@compound(['loading' => true], targets: [
    'button' => ['pointer-events-none cursor-wait'],
    'content' => ['opacity-0' => $overlayLoading],
    'loading' => ['absolute inset-0 overflow-hidden' => $loading],
])

<x-malevich::primitive @attributes('button', slot: $attributes, override: ['aria-busy' => $loading ? 'true' : null]) :as="$as" :disabled="$disabled || $loading" >
    <span @attributes('content', merge: ['aria-hidden' => $overlayLoading && !$square && $hasLoadingLabel ? 'true' : null])>
        @if ($loading && !$overlayLoading)
            <span @attributes('spinner') aria-hidden="true"></span>
        @elseif (filled($leading))
            <span @attributes('leading')>{{ $leading }}</span>
        @endif
        {{ $slot }}
        @if (filled($trailing))
            <span @attributes('trailing')>{{ $trailing }}</span>
        @endif
    </span>
    @if ($overlayLoading)
        <span @attributes('loading')>
            <span @attributes('spinner') aria-hidden="true"></span>
            @if (!$square && $hasLoadingLabel)<span @attributes('loadingLabel')>{{ $loadingLabel }}</span>@endif
        </span>
    @endif
</x-malevich::primitive>
