@props([
    'variant' => 'solid',
    'color' => 'primary',
    'size' => 'md',
    'radius' => 'brand',

    'block' => false,
    'square' => null,

    'loading' => false,
    'disabled' => false,

    'leading' => null,
    'trailing' => null,

    'as' => 'button',
])

{{-- @validate([
    'variant' => [
        'rule' => ['solid', 'soft', 'surface', 'elevated', 'outline', 'ghost', 'link'],
        'fallback' => 'solid',
    ],
    'color' => [
        'rule' => ['primary', 'secondary', 'success', 'info', 'warning', 'danger'],
        'fallback' => 'primary',
    ],
])

@fallback([
    'variant' => 'solid',
    'color' => 'primary',
    'size' => 'md',
    'radius' => 'brand',
]) --}}

@base('button', [
    'relative inline-flex shrink-0 items-center justify-center align-middle',
    'border',
    'font-medium whitespace-nowrap select-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2',
    'data-disabled:pointer-events-none data-disabled:opacity-70 data-disabled:cursor-not-allowed',
    'active:translate-y-px',
    'aspect-square p-0' => $square,
])

@cases('variant', [
    'solid' => [
        'button' => [
            'border-transparent',
        ],
    ],
    'soft' => [
        'button' => [
            'border-transparent',
        ],
    ],
    'surface' => [
        'button' => [
            '',
        ],
    ],
    'elevated' => [
        'button' => [
            'shadow-xs',
            'bg-white border-gray-200 hover:bg-gray-50',
            'dark:bg-white/5 dark:border-white/10 dark:hover:bg-white/10',
        ],
    ],
    'outline' => [
        'button' => [
            '',
        ],
    ],
    'ghost' => [
        'button' => [
            'border-transparent'
        ],
    ],
    'link' => [
        'button' => [
            'border-transparent',
            'underline-offset-4 hover:underline',
        ],
    ],
])

@cases('color', [
    'primary' => [
        'button' => [
            'bg-primary-500 text-white',
            'hover:bg-primary-600/90 active:bg-primary-600',
            'dark:hover:bg-primary-400/90 dark:active:bg-primary-400',
        ],
    ],
    'success' => [
        'button' => [
            'bg-success-500 text-success-950',
            'hover:bg-success-600/90 active:bg-success-600',
            'dark:hover:bg-success-400/90 dark:active:bg-success-400 dark:text-success-950',
        ],
    ],
    'info' => [
        'button' => [
            'bg-info-500 text-white',
            'hover:bg-info-600/90 active:bg-info-600',
            'dark:hover:bg-info-400/90 dark:active:bg-info-400',
        ],
    ],
    'warning' => [
        'button' => [
            'bg-warning-500 text-warning-950',
            'hover:bg-warning-600/90 active:bg-warning-600',
            'dark:hover:bg-warning-400/90 dark:active:bg-warning-400 dark:text-warning-950',
        ],
    ],
    'danger' => [
        'button' => [
            'bg-danger-500 text-white',
            'hover:bg-danger-600/90 active:bg-danger-600',
            'dark:hover:bg-danger-400/90 dark:active:bg-danger-400',
        ],
    ],
], when: [
    'variant' => ['solid'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'bg-primary-100 text-primary-600 hover:bg-primary-200 active:bg-primary-300',
            'dark:bg-primary-500/20 dark:hover:bg-primary-500/30 dark:active:bg-primary-500/40 dark:text-primary-400',
        ],
    ],
    'success' => [
        'button' => [
            'bg-success-100 text-success-600 hover:bg-success-200 active:bg-success-300',
            'dark:bg-success-500/20 dark:hover:bg-success-500/30 dark:active:bg-success-500/40 dark:text-success-400',
        ],
    ],
    'info' => [
        'button' => [
            'bg-info-100 text-info-600 hover:bg-info-200 active:bg-info-300',
            'dark:bg-info-500/20 dark:hover:bg-info-500/30 dark:active:bg-info-500/40 dark:text-info-400',
        ],
    ],
    'warning' => [
        'button' => [
            'bg-warning-100 text-warning-600 hover:bg-warning-200 active:bg-warning-300',
            'dark:bg-warning-500/20 dark:hover:bg-warning-500/30 dark:active:bg-warning-500/40 dark:text-warning-400',
        ],
    ],
    'danger' => [
        'button' => [
            'bg-danger-100 text-danger-600 hover:bg-danger-200 active:bg-danger-300',
            'dark:bg-danger-500/20 dark:hover:bg-danger-500/30 dark:active:bg-danger-500/40 dark:text-danger-400',
        ],
    ],
], when: [
    'variant' => ['soft'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'bg-primary-100 text-primary-600 hover:bg-primary-200 active:bg-primary-300 border-primary-200 hover:border-primary-300',
            'dark:bg-primary-500/20 dark:hover:bg-primary-500/30 dark:active:bg-primary-500/40 dark:text-primary-400 dark:border-primary-700 dark:hover:border-primary-600',
        ],
    ],
    'success' => [
        'button' => [
            'bg-success-100 text-success-600 hover:bg-success-200 active:bg-success-300 border-success-200 hover:border-success-300',
            'dark:bg-success-500/20 dark:hover:bg-success-500/30 dark:active:bg-success-500/40 dark:text-success-400 dark:border-success-700 dark:hover:border-success-600',
        ],
    ],
    'info' => [
        'button' => [
            'bg-info-100 text-info-600 hover:bg-info-200 active:bg-info-300 border-info-200 hover:border-info-300',
            'dark:bg-info-500/20 dark:hover:bg-info-500/30 dark:active:bg-info-500/40 dark:text-info-400 dark:border-info-700 dark:hover:border-info-600',
        ],
    ],
    'warning' => [
        'button' => [
            'bg-warning-100 text-warning-600 hover:bg-warning-200 active:bg-warning-300 border-warning-200 hover:border-warning-300',
            'dark:bg-warning-500/20 dark:hover:bg-warning-500/30 dark:active:bg-warning-500/40 dark:text-warning-400 dark:border-warning-700 dark:hover:border-warning-600',
        ],
    ],
    'danger' => [
        'button' => [
            'bg-danger-100 text-danger-600 hover:bg-danger-200 active:bg-danger-300 border-danger-200 hover:border-danger-300',
            'dark:bg-danger-500/20 dark:hover:bg-danger-500/30 dark:active:bg-danger-500/40 dark:text-danger-400 dark:border-danger-700 dark:hover:border-danger-600',
        ],
    ],
], when: [
    'variant' => ['surface'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'text-primary-500 dark:text-primary-400',
        ],
    ],
    'success' => [
        'button' => [
            'text-success-500 dark:text-success-400',
        ],
    ],
    'info' => [
        'button' => [
            'text-info-500 dark:text-info-400',
        ],
    ],
    'warning' => [
        'button' => [
            'text-warning-500 dark:text-warning-400',
        ],
    ],
    'danger' => [
        'button' => [
            'text-danger-500 dark:text-danger-400',
        ],
    ],
], when: [
    'variant' => ['elevated'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'bg-transparent',
            'text-primary-500 hover:bg-primary-500 hover:text-white active:bg-primary-600 border-primary-300 hover:border-primary-500 active:border-primary-600',
            'dark:text-primary-400 dark:hover:bg-primary-500 dark:hover:text-white dark:active:bg-primary-400 dark:border-primary-700 dark:hover:border-primary-500 dark:active:border-primary-400',
        ],
    ],
    'success' => [
        'button' => [
            'bg-transparent',
            'text-success-500 hover:bg-success-500 hover:text-success-950 active:bg-success-600 active:text-success-950 border-success-300 hover:border-success-500 active:border-success-600',
            'dark:text-success-400 dark:hover:bg-success-500 dark:hover:text-success-950 dark:active:bg-success-400 dark:active:text-success-950 dark:border-success-700 dark:hover:border-success-500 dark:active:border-success-400',
        ],
    ],
    'info' => [
        'button' => [
            'bg-transparent',
            'text-info-500 hover:bg-info-500 hover:text-white active:bg-info-600 border-info-300 hover:border-info-500 active:border-info-600',
            'dark:text-info-400 dark:hover:bg-info-500 dark:hover:text-white dark:active:bg-info-400 dark:border-info-700 dark:hover:border-info-500 dark:active:border-info-400',
        ],
    ],
    'warning' => [
        'button' => [
            'bg-transparent',
            'text-warning-500 hover:bg-warning-500 hover:text-warning-950 active:bg-warning-600 active:text-warning-950 border-warning-300 hover:border-warning-500 active:border-warning-600',
            'dark:text-warning-400 dark:hover:bg-warning-500 dark:hover:text-warning-950 dark:active:bg-warning-400 dark:active:text-warning-950 dark:border-warning-700 dark:hover:border-warning-500 dark:active:border-warning-400',
        ],
    ],
    'danger' => [
        'button' => [
            'bg-transparent',
            'text-danger-500 hover:bg-danger-500 hover:text-white active:bg-danger-600 border-danger-300 hover:border-danger-500 active:border-danger-600',
            'dark:text-danger-400 dark:hover:bg-danger-500 dark:hover:text-white dark:active:bg-danger-400 dark:border-danger-700 dark:hover:border-danger-500 dark:active:border-danger-400',
        ],
    ],
], when: [
    'variant' => ['outline'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'bg-transparent',
            'hover:bg-primary-100 active:bg-primary-200 text-primary-600',
            'dark:hover:bg-primary-500/20 dark:active:bg-primary-500/30 dark:text-primary-400',
        ],
    ],
    'success' => [
        'button' => [
            'bg-transparent',
            'hover:bg-success-100 active:bg-success-200 text-success-600',
            'dark:hover:bg-success-500/20 dark:active:bg-success-500/30 dark:text-success-400',
        ],
    ],
    'info' => [
        'button' => [
            'bg-transparent',
            'hover:bg-info-100 active:bg-info-200 text-info-600',
            'dark:hover:bg-info-500/20 dark:active:bg-info-500/30 dark:text-info-400',
        ],
    ],
    'warning' => [
        'button' => [
            'bg-transparent',
            'hover:bg-warning-100 active:bg-warning-200 text-warning-600',
            'dark:hover:bg-warning-500/20 dark:active:bg-warning-500/30 dark:text-warning-400',
        ],
    ],
    'danger' => [
        'button' => [
            'bg-transparent',
            'hover:bg-danger-100 active:bg-danger-200 text-danger-600',
            'dark:hover:bg-danger-500/20 dark:active:bg-danger-500/30 dark:text-danger-400',
        ],
    ],
], when: [
    'variant' => ['ghost'],
])

@cases('color', [
    'primary' => [
        'button' => [
            'text-primary-600 hover:text-primary-700 active:text-primary-800',
            'dark:text-primary-500 dark:hover:text-primary-400 dark:active:text-primary-300',
        ],
    ],
    'success' => [
        'button' => [
            'text-success-600 hover:text-success-700 active:text-success-800',
            'dark:text-success-500 dark:hover:text-success-400 dark:active:text-success-300',
        ],
    ],
    'info' => [
        'button' => [
            'text-info-600 hover:text-info-700 active:text-info-800',
            'dark:text-info-500 dark:hover:text-info-400 dark:active:text-info-300',
        ],
    ],
    'warning' => [
        'button' => [
            'text-warning-600 hover:text-warning-700 active:text-warning-800',
            'dark:text-warning-500 dark:hover:text-warning-400 dark:active:text-warning-300',
        ],
    ],
    'danger' => [
        'button' => [
            'text-danger-600 hover:text-danger-700 active:text-danger-800',
            'dark:text-danger-500 dark:hover:text-danger-400 dark:active:text-danger-300',
        ],
    ],
], when: [
    'variant' => ['link'],
])

@size('button', [
    'xs' => ['text-xs', 'px-1.5 h-6' => !$block, 'size-6' => $block],
    'sm' => ['text-[0.8rem] leading-[0.8rem]', 'px-2.5 h-7' => !$block, 'size-7' => $block],
    'md' => ['text-sm', 'px-3 h-8' => !$block, 'size-8' => $block],
    'lg' => ['text-sm', 'px-4 h-9' => !$block, 'size-9' => $block],
    'xl' => ['text-sm', 'px-5 h-10' => !$block, 'size-10' => $block],
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

@compound(['block' => true], 'w-full')

@compound(['loading' => true], targets: [
    'button' => ['pointer-events-none cursor-wait'],
    'content' => ['opacity-0'],
    'loading' => ['absolute inset-0 overflow-hidden' => $loading],
])

<x-malevich::primitive @attributes('button') :as="$as" :disabled="$disabled || $loading" >
    <span @attributes('content')>
        @if (filled($leading))
            <span @attributes('leading')>{{ $leading }}</span>
        @endif
        {{ $slot }}
        @if (filled($trailing))
            <span @attributes('trailing')>{{ $trailing }}</span>
        @endif
    </span>
</x-malevich::primitive>
