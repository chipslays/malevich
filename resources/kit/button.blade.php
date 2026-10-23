{{-- <x-button variant="solid" color="primary">Continue</x-button> --}}

@props([
    'variant' => 'solid',
    'color' => 'primary',
    'size' => 'md',
    'radius' => 'brand',
    'inset' => false,
    'loading' => false,
    'loadingLabel' => null,
    'disabled' => false,
    'icon' => null,
    'trailing' => null,
    'as' => 'button',
])

@php
    $iconOnly = trim(strip_tags((string) $slot)) === '';
    $overlayLoading = $loading && ($iconOnly || $loadingLabel !== null);
    $hasLoadingLabel = ! $iconOnly && $loadingLabel !== null && (
        $loadingLabel instanceof \Illuminate\View\ComponentSlot
            ? $loadingLabel->hasActualContent()
            : trim((string) $loadingLabel) !== ''
    );
@endphp

{{-- Appearance: treatment and color are independent. --}}
@variant([
    'solid' => 'border-transparent',
    'soft' => 'border-transparent',
    'outline' => 'bg-transparent',
    'ghost' => 'border-transparent bg-transparent',
    'link' => ['border-transparent bg-transparent', 'underline-offset-4 hover:underline'],
])

@color([
    'primary' => 'focus-visible:outline-primary-600 dark:focus-visible:outline-primary-300',
    'secondary' => 'focus-visible:outline-secondary-600 dark:focus-visible:outline-secondary-300',
    'accent' => 'focus-visible:outline-accent-600 dark:focus-visible:outline-accent-300',
    'neutral' => 'focus-visible:outline-gray-600 dark:focus-visible:outline-gray-300',
    'contrast' => 'focus-visible:outline-gray-950 dark:focus-visible:outline-gray-100',
    'success' => 'focus-visible:outline-success-600 dark:focus-visible:outline-success-300',
    'warning' => 'focus-visible:outline-warning-600 dark:focus-visible:outline-warning-300',
    'danger' => 'focus-visible:outline-danger-600 dark:focus-visible:outline-danger-300',
    'info' => 'focus-visible:outline-info-600 dark:focus-visible:outline-info-300',
])

{{-- Palette data: complete Tailwind classes, shared by the rules below. --}}
@php
    $palette = [
        'primary' => [
            'solid' => [
                'bg-primary-600 text-white hover:bg-primary-700',
            ],
            'soft' => [
                'bg-primary-50 hover:bg-primary-100',
                'dark:bg-primary-950 dark:hover:bg-primary-900',
            ],
            'foreground' => 'text-primary-700 dark:text-primary-300',
            'outline' => 'border-primary-300 dark:border-primary-700',
            'hover' => 'hover:bg-primary-50 dark:hover:bg-primary-950',
        ],
        'secondary' => [
            'solid' => [
                'bg-secondary-600 text-white hover:bg-secondary-700',
            ],
            'soft' => [
                'bg-secondary-50 hover:bg-secondary-100',
                'dark:bg-secondary-950 dark:hover:bg-secondary-900',
            ],
            'foreground' => 'text-secondary-700 dark:text-secondary-300',
            'outline' => 'border-secondary-300 dark:border-secondary-700',
            'hover' => 'hover:bg-secondary-50 dark:hover:bg-secondary-950',
        ],
        'accent' => [
            'solid' => [
                'bg-accent-600 text-white hover:bg-accent-700',
            ],
            'soft' => [
                'bg-accent-50 hover:bg-accent-100',
                'dark:bg-accent-950 dark:hover:bg-accent-900',
            ],
            'foreground' => 'text-accent-700 dark:text-accent-300',
            'outline' => 'border-accent-300 dark:border-accent-700',
            'hover' => 'hover:bg-accent-50 dark:hover:bg-accent-950',
        ],
        'neutral' => [
            'solid' => [
                'bg-gray-100 text-gray-950 hover:bg-gray-200',
                'dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800',
            ],
            'soft' => [
                'bg-gray-50 hover:bg-gray-100',
                'dark:bg-gray-950 dark:hover:bg-gray-900',
            ],
            'foreground' => 'text-gray-800 dark:text-gray-200',
            'outline' => 'border-gray-300 dark:border-gray-700',
            'hover' => 'hover:bg-gray-50 dark:hover:bg-gray-950',
        ],
        'contrast' => [
            'solid' => [
                'bg-gray-950 text-white hover:bg-gray-800',
                'dark:bg-gray-100 dark:text-gray-950 dark:hover:bg-gray-300',
            ],
            'soft' => [
                'bg-gray-950/10 hover:bg-gray-950/15',
                'dark:bg-gray-100/15 dark:hover:bg-gray-100/20',
            ],
            'foreground' => 'text-gray-950 dark:text-gray-50',
            'outline' => 'border-gray-950 dark:border-gray-100',
            'hover' => 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15',
        ],
        'success' => [
            'solid' => [
                'bg-success-700 text-white hover:bg-success-800',
            ],
            'soft' => [
                'bg-success-50 hover:bg-success-100',
                'dark:bg-success-950 dark:hover:bg-success-900',
            ],
            'foreground' => 'text-success-700 dark:text-success-300',
            'outline' => 'border-success-300 dark:border-success-700',
            'hover' => 'hover:bg-success-50 dark:hover:bg-success-950',
        ],
        'warning' => [
            'solid' => [
                'bg-warning-700 text-white hover:bg-warning-800',
            ],
            'soft' => [
                'bg-warning-50 hover:bg-warning-100',
                'dark:bg-warning-950 dark:hover:bg-warning-900',
            ],
            'foreground' => 'text-warning-800 dark:text-warning-300',
            'outline' => 'border-warning-300 dark:border-warning-700',
            'hover' => 'hover:bg-warning-50 dark:hover:bg-warning-950',
        ],
        'danger' => [
            'solid' => [
                'bg-danger-600 text-white hover:bg-danger-700',
            ],
            'soft' => [
                'bg-danger-50 hover:bg-danger-100',
                'dark:bg-danger-950 dark:hover:bg-danger-900',
            ],
            'foreground' => 'text-danger-700 dark:text-danger-300',
            'outline' => 'border-danger-300 dark:border-danger-700',
            'hover' => 'hover:bg-danger-50 dark:hover:bg-danger-950',
        ],
        'info' => [
            'solid' => [
                'bg-info-600 text-white hover:bg-info-700',
            ],
            'soft' => [
                'bg-info-50 hover:bg-info-100',
                'dark:bg-info-950 dark:hover:bg-info-900',
            ],
            'foreground' => 'text-info-700 dark:text-info-300',
            'outline' => 'border-info-300 dark:border-info-700',
            'hover' => 'hover:bg-info-50 dark:hover:bg-info-950',
        ],
    ];

    // Share palette fragments across treatments without repeating their classes.
    $recipe = \Malevich\Malevich::recipe($attributes);

    foreach ($palette as $colorName => $styles) {
        foreach (['solid', 'soft', 'outline'] as $treatment) {
            $recipe->compound(['variant' => $treatment, 'color' => $colorName], $styles[$treatment]);
        }

        $recipe->compound([
            'variant' => ['soft', 'outline', 'ghost', 'link'],
            'color' => $colorName,
        ], $styles['foreground']);

        $recipe->compound([
            'variant' => ['outline', 'ghost'],
            'color' => $colorName,
        ], $styles['hover']);
    }
@endphp

{{-- One size describes all parts; each target can still override it. --}}
@cases('size', [
    'xs' => [
        'default' => ['h-7 gap-1 text-xs', 'px-2' => ! $iconOnly],
        'content' => 'gap-1',
        'loading' => 'gap-1',
        'icon' => '[&>svg]:size-3.5',
        'trailing' => '[&>svg]:size-3.5',
        'spinner' => 'size-3.5',
    ],
    'sm' => [
        'default' => ['h-8 gap-1.5 text-sm', 'px-3' => ! $iconOnly],
        'content' => 'gap-1.5',
        'loading' => 'gap-1.5',
        'icon' => '[&>svg]:size-4',
        'trailing' => '[&>svg]:size-4',
        'spinner' => 'size-4',
    ],
    'md' => [
        'default' => ['h-10 gap-2 text-sm', 'px-4' => ! $iconOnly],
        'content' => 'gap-2',
        'loading' => 'gap-2',
        'icon' => '[&>svg]:size-4.5',
        'trailing' => '[&>svg]:size-4.5',
        'spinner' => 'size-4.5',
    ],
    'lg' => [
        'default' => ['h-11 gap-2 text-base', 'px-5' => ! $iconOnly],
        'content' => 'gap-2',
        'loading' => 'gap-2',
        'icon' => '[&>svg]:size-5',
        'trailing' => '[&>svg]:size-5',
        'spinner' => 'size-5',
    ],
    'xl' => [
        'default' => ['h-12 gap-2.5 text-base', 'px-6' => ! $iconOnly],
        'content' => 'gap-2.5',
        'loading' => 'gap-2.5',
        'icon' => '[&>svg]:size-5.5',
        'trailing' => '[&>svg]:size-5.5',
        'spinner' => 'size-5.5',
    ],
])

{{-- Transparent treatments can align their label with adjacent content. --}}
@cases('size', [
    'xs' => ['-mx-2' => ! $iconOnly],
    'sm' => ['-mx-3' => ! $iconOnly],
    'md' => ['-mx-4' => ! $iconOnly],
    'lg' => ['-mx-5' => ! $iconOnly],
    'xl' => ['-mx-6' => ! $iconOnly],
], when: [
    'inset' => true,
    'variant' => ['ghost', 'link'],
])

@option('radius', [
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

{{-- Mechanics: preserve the label by default; explicit replacements overlay the original. --}}
@base([
    'relative inline-flex shrink-0 items-center justify-center align-middle',
    'border font-medium whitespace-nowrap select-none',
    'transition-colors motion-reduce:transition-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2',
    'data-disabled:pointer-events-none data-disabled:opacity-50',
    'aspect-square p-0' => $iconOnly,
])

@base('content', 'inline-flex items-center justify-center')
@base('loading', 'inline-flex shrink-0 items-center justify-center')
@base('loadingLabel', 'truncate')
@base('icon', 'inline-flex shrink-0')
@base('trailing', 'inline-flex shrink-0')
@base('spinner', 'shrink-0 animate-spin motion-reduce:animate-none')

@compound(['loading' => true], targets: [
    'default' => 'pointer-events-none',
    'content' => ['opacity-0' => $overlayLoading],
    'loading' => [
        'absolute inset-0 overflow-hidden' => $overlayLoading,
        'px-2' => $overlayLoading && ! $iconOnly,
    ],
])

@php
    if ($loading) {
        $attributes = $attributes->except('aria-busy')->merge(['aria-busy' => 'true']);
    }
@endphp

<x-malevich::primitive :as="$as" :disabled="$disabled || $loading" @attributes>
    @if ($loading)
        <span @attributes('loading', merge: ['aria-hidden' => $hasLoadingLabel ? null : 'true'])>
            <svg @attributes('spinner', merge: ['aria-hidden' => 'true']) viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2" />
                <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>

            @if ($hasLoadingLabel)
                <span @attributes('loadingLabel')>{{ $loadingLabel }}</span>
            @endif
        </span>
    @endif

    <span @attributes('content', merge: ['aria-hidden' => $overlayLoading && $hasLoadingLabel ? 'true' : null])>
        @if ($icon !== null && (! $loading || $overlayLoading))
            <span @attributes('icon', merge: ['aria-hidden' => 'true'])>{{ $icon }}</span>
        @endif

        {{ $slot }}

        @if ($trailing !== null)
            <span @attributes('trailing', merge: ['aria-hidden' => 'true'])>{{ $trailing }}</span>
        @endif
    </span>
</x-malevich::primitive>
