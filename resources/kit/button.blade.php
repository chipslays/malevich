{{-- <x-button variant="solid" color="primary">Continue</x-button> --}}

@props([
    'variant' => 'solid',
    'color' => 'primary',
    'size' => 'md',
    'radius' => 'brand',
    'inset' => false,
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
    // Square by default when the default slot has no text, e.g. only an icon.
    $square = $square === null ? trim(strip_tags((string) $slot)) === '' : filter_var($square, FILTER_VALIDATE_BOOL);
    $overlayLoading = $loading && ($square || $loadingLabel !== null);
    $hasLoadingLabel = ! $square && $loadingLabel !== null && (
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

{{-- Solid: a filled background with its own text color. --}}
@cases('color', [
    'primary' => 'bg-primary-600 text-white hover:bg-primary-700',
    'secondary' => 'bg-secondary-600 text-white hover:bg-secondary-700',
    'accent' => 'bg-accent-600 text-white hover:bg-accent-700',
    'neutral' => ['bg-gray-100 text-gray-950 hover:bg-gray-200', 'dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800'],
    'contrast' => ['bg-gray-950 text-white hover:bg-gray-800', 'dark:bg-gray-100 dark:text-gray-950 dark:hover:bg-gray-300'],
    'success' => 'bg-success-500 text-white hover:bg-success-600',
    'warning' => 'bg-warning-500 text-black hover:bg-warning-600',
    'danger' => 'bg-danger-500 text-white hover:bg-danger-600',
    'info' => 'bg-info-500 text-white hover:bg-info-600',
], when: ['variant' => 'solid'])

{{-- Soft: a tinted background. --}}
@cases('color', [
    'primary' => ['bg-primary-50 hover:bg-primary-100', 'dark:bg-primary-950 dark:hover:bg-primary-900'],
    'secondary' => ['bg-secondary-50 hover:bg-secondary-100', 'dark:bg-secondary-950 dark:hover:bg-secondary-900'],
    'accent' => ['bg-accent-50 hover:bg-accent-100', 'dark:bg-accent-950 dark:hover:bg-accent-900'],
    'neutral' => ['bg-gray-50 hover:bg-gray-100', 'dark:bg-gray-950 dark:hover:bg-gray-900'],
    'contrast' => ['bg-gray-950/10 hover:bg-gray-950/15', 'dark:bg-gray-100/15 dark:hover:bg-gray-100/20'],
    'success' => ['bg-success-100 hover:bg-success-200', 'dark:bg-success-950 dark:hover:bg-success-900'],
    'warning' => ['bg-warning-100 hover:bg-warning-200', 'dark:bg-warning-950 dark:hover:bg-warning-900'],
    'danger' => ['bg-danger-100 hover:bg-danger-200', 'dark:bg-danger-950 dark:hover:bg-danger-900'],
    'info' => ['bg-info-100 hover:bg-info-200', 'dark:bg-info-950 dark:hover:bg-info-900'],
], when: ['variant' => 'soft'])

{{-- Outline: a colored border. --}}
@cases('color', [
    'primary' => 'border-primary-300 dark:border-primary-700',
    'secondary' => 'border-secondary-300 dark:border-secondary-700',
    'accent' => 'border-accent-300 dark:border-accent-700',
    'neutral' => 'border-gray-300 dark:border-gray-700',
    'contrast' => 'border-gray-950 dark:border-gray-100',
    'success' => 'border-success-300 dark:border-success-700',
    'warning' => 'border-warning-300 dark:border-warning-700',
    'danger' => 'border-danger-300 dark:border-danger-700',
    'info' => 'border-info-300 dark:border-info-700',
], when: ['variant' => 'outline'])

{{-- Every treatment without a filled background takes its text color from the role. --}}
@cases('color', [
    'primary' => 'text-primary-700 dark:text-primary-300',
    'secondary' => 'text-secondary-700 dark:text-secondary-300',
    'accent' => 'text-accent-700 dark:text-accent-300',
    'neutral' => 'text-gray-800 dark:text-gray-200',
    'contrast' => 'text-gray-950 dark:text-gray-50',
    'success' => 'text-success-800 dark:text-success-300',
    'warning' => 'text-warning-800 dark:text-warning-300',
    'danger' => 'text-danger-700 dark:text-danger-300',
    'info' => 'text-info-700 dark:text-info-300',
], when: ['variant' => ['soft', 'outline', 'ghost', 'link']])

{{-- Transparent treatments get a tinted background on hover. --}}
@cases('color', [
    'primary' => 'hover:bg-primary-50 dark:hover:bg-primary-950',
    'secondary' => 'hover:bg-secondary-50 dark:hover:bg-secondary-950',
    'accent' => 'hover:bg-accent-50 dark:hover:bg-accent-950',
    'neutral' => 'hover:bg-gray-50 dark:hover:bg-gray-950',
    'contrast' => 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15',
    'success' => 'hover:bg-success-50 dark:hover:bg-success-950',
    'warning' => 'hover:bg-warning-50 dark:hover:bg-warning-950',
    'danger' => 'hover:bg-danger-50 dark:hover:bg-danger-950',
    'info' => 'hover:bg-info-50 dark:hover:bg-info-950',
], when: ['variant' => ['outline', 'ghost']])

{{-- One size describes all parts; each target can still override it. --}}
@cases('size', [
    'xs' => [
        'default' => ['h-7 gap-1 text-xs', 'px-2' => ! $square],
        'content' => 'gap-1',
        'loading' => 'gap-1',
        'leading' => '[&>svg]:size-3.5',
        'trailing' => '[&>svg]:size-3.5',
        'spinner' => 'size-3.5',
    ],
    'sm' => [
        'default' => ['h-8 gap-1.5 text-sm', 'px-3' => ! $square],
        'content' => 'gap-1.5',
        'loading' => 'gap-1.5',
        'leading' => '[&>svg]:size-4',
        'trailing' => '[&>svg]:size-4',
        'spinner' => 'size-4',
    ],
    'md' => [
        'default' => ['h-10 gap-2 text-sm', 'px-4' => ! $square],
        'content' => 'gap-2',
        'loading' => 'gap-2',
        'leading' => '[&>svg]:size-4.5',
        'trailing' => '[&>svg]:size-4.5',
        'spinner' => 'size-4.5',
    ],
    'lg' => [
        'default' => ['h-11 gap-2 text-base', 'px-5' => ! $square],
        'content' => 'gap-2',
        'loading' => 'gap-2',
        'leading' => '[&>svg]:size-5',
        'trailing' => '[&>svg]:size-5',
        'spinner' => 'size-5',
    ],
    'xl' => [
        'default' => ['h-12 gap-2.5 text-base', 'px-6' => ! $square],
        'content' => 'gap-2.5',
        'loading' => 'gap-2.5',
        'leading' => '[&>svg]:size-5.5',
        'trailing' => '[&>svg]:size-5.5',
        'spinner' => 'size-5.5',
    ],
])

{{-- Transparent treatments can align their label with adjacent content. --}}
@cases('size', [
    'xs' => ['-mx-2' => ! $square],
    'sm' => ['-mx-3' => ! $square],
    'md' => ['-mx-4' => ! $square],
    'lg' => ['-mx-5' => ! $square],
    'xl' => ['-mx-6' => ! $square],
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
    'focus-visible:outline-2 focus-visible:outline-offset-2',
    'data-disabled:pointer-events-none data-disabled:opacity-50',
    'aspect-square p-0' => $square,
])

@base('content', 'inline-flex items-center justify-center')
@base('loading', 'inline-flex shrink-0 items-center justify-center')
@base('loadingLabel', 'truncate')
@base('leading', 'inline-flex shrink-0')
@base('trailing', 'inline-flex shrink-0')
@base('spinner', 'shrink-0 animate-spin motion-reduce:animate-none')

@compound(['block' => true], 'w-full')

@compound(['loading' => true], targets: [
    'default' => 'pointer-events-none',
    'content' => ['opacity-0' => $overlayLoading],
    'loading' => [
        'absolute inset-0 overflow-hidden' => $overlayLoading,
        'px-2' => $overlayLoading && ! $square,
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
        @if ($leading !== null && (! $loading || $overlayLoading))
            <span @attributes('leading', merge: ['aria-hidden' => 'true'])>{{ $leading }}</span>
        @endif

        {{ $slot }}

        @if ($trailing !== null)
            <span @attributes('trailing', merge: ['aria-hidden' => 'true'])>{{ $trailing }}</span>
        @endif
    </span>
</x-malevich::primitive>
