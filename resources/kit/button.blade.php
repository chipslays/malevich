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
    'primary' => 'focus-visible:outline-indigo-600 dark:focus-visible:outline-indigo-300',
    'secondary' => 'focus-visible:outline-slate-600 dark:focus-visible:outline-slate-300',
    'accent' => 'focus-visible:outline-violet-600 dark:focus-visible:outline-violet-300',
    'neutral' => 'focus-visible:outline-neutral-600 dark:focus-visible:outline-neutral-300',
    'contrast' => 'focus-visible:outline-neutral-600 dark:focus-visible:outline-neutral-300',
    'success' => 'focus-visible:outline-green-600 dark:focus-visible:outline-green-300',
    'warning' => 'focus-visible:outline-amber-600 dark:focus-visible:outline-amber-300',
    'danger' => 'focus-visible:outline-red-600 dark:focus-visible:outline-red-300',
    'info' => 'focus-visible:outline-blue-600 dark:focus-visible:outline-blue-300',
])

{{-- Palette data: complete Tailwind classes, shared by the rules below. --}}
@php
    $palette = [
        'primary' => [
            'solid' => [
                'bg-indigo-600 text-white hover:bg-indigo-700',
            ],
            'soft' => [
                'bg-indigo-50 hover:bg-indigo-100',
                'dark:bg-indigo-950 dark:hover:bg-indigo-900',
            ],
            'foreground' => 'text-indigo-700 dark:text-indigo-300',
            'outline' => 'border-indigo-300 dark:border-indigo-700',
            'hover' => 'hover:bg-indigo-50 dark:hover:bg-indigo-950',
        ],
        'secondary' => [
            'solid' => [
                'bg-slate-600 text-white hover:bg-slate-700',
            ],
            'soft' => [
                'bg-slate-50 hover:bg-slate-100',
                'dark:bg-slate-950 dark:hover:bg-slate-900',
            ],
            'foreground' => 'text-slate-700 dark:text-slate-300',
            'outline' => 'border-slate-300 dark:border-slate-700',
            'hover' => 'hover:bg-slate-50 dark:hover:bg-slate-950',
        ],
        'accent' => [
            'solid' => [
                'bg-violet-600 text-white hover:bg-violet-700',
            ],
            'soft' => [
                'bg-violet-50 hover:bg-violet-100',
                'dark:bg-violet-950 dark:hover:bg-violet-900',
            ],
            'foreground' => 'text-violet-700 dark:text-violet-300',
            'outline' => 'border-violet-300 dark:border-violet-700',
            'hover' => 'hover:bg-violet-50 dark:hover:bg-violet-950',
        ],
        'neutral' => [
            'solid' => [
                'bg-neutral-100 text-neutral-950 hover:bg-neutral-200',
                'dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800',
            ],
            'soft' => [
                'bg-neutral-50 hover:bg-neutral-100',
                'dark:bg-neutral-950 dark:hover:bg-neutral-900',
            ],
            'foreground' => 'text-neutral-800 dark:text-neutral-200',
            'outline' => 'border-neutral-300 dark:border-neutral-700',
            'hover' => 'hover:bg-neutral-50 dark:hover:bg-neutral-950',
        ],
        'success' => [
            'solid' => [
                'bg-green-700 text-white hover:bg-green-800',
            ],
            'soft' => [
                'bg-green-50 hover:bg-green-100',
                'dark:bg-green-950 dark:hover:bg-green-900',
            ],
            'foreground' => 'text-green-700 dark:text-green-300',
            'outline' => 'border-green-300 dark:border-green-700',
            'hover' => 'hover:bg-green-50 dark:hover:bg-green-950',
        ],
        'warning' => [
            'solid' => [
                'bg-amber-700 text-white hover:bg-amber-800',
            ],
            'soft' => [
                'bg-amber-50 hover:bg-amber-100',
                'dark:bg-amber-950 dark:hover:bg-amber-900',
            ],
            'foreground' => 'text-amber-800 dark:text-amber-300',
            'outline' => 'border-amber-300 dark:border-amber-700',
            'hover' => 'hover:bg-amber-50 dark:hover:bg-amber-950',
        ],
        'danger' => [
            'solid' => [
                'bg-red-600 text-white hover:bg-red-700',
            ],
            'soft' => [
                'bg-red-50 hover:bg-red-100',
                'dark:bg-red-950 dark:hover:bg-red-900',
            ],
            'foreground' => 'text-red-700 dark:text-red-300',
            'outline' => 'border-red-300 dark:border-red-700',
            'hover' => 'hover:bg-red-50 dark:hover:bg-red-950',
        ],
        'info' => [
            'solid' => [
                'bg-blue-600 text-white hover:bg-blue-700',
            ],
            'soft' => [
                'bg-blue-50 hover:bg-blue-100',
                'dark:bg-blue-950 dark:hover:bg-blue-900',
            ],
            'foreground' => 'text-blue-700 dark:text-blue-300',
            'outline' => 'border-blue-300 dark:border-blue-700',
            'hover' => 'hover:bg-blue-50 dark:hover:bg-blue-950',
        ],
    ];

    // Contrast shares neutral's transparent treatments and reverses its solid fill.
    $palette['contrast'] = array_replace($palette['neutral'], [
        'solid' => [
            'bg-neutral-950 text-white hover:bg-neutral-800',
            'dark:bg-neutral-100 dark:text-neutral-950 dark:hover:bg-neutral-300',
        ],
    ]);

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
@compound(['inset' => true, 'variant' => ['ghost', 'link'], 'size' => 'xs'], ['-mx-2' => ! $iconOnly])
@compound(['inset' => true, 'variant' => ['ghost', 'link'], 'size' => 'sm'], ['-mx-3' => ! $iconOnly])
@compound(['inset' => true, 'variant' => ['ghost', 'link'], 'size' => 'md'], ['-mx-4' => ! $iconOnly])
@compound(['inset' => true, 'variant' => ['ghost', 'link'], 'size' => 'lg'], ['-mx-5' => ! $iconOnly])
@compound(['inset' => true, 'variant' => ['ghost', 'link'], 'size' => 'xl'], ['-mx-6' => ! $iconOnly])

@option('radius', [
    'brand' => 'rounded-lg',
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
