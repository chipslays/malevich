{{-- <x-badge color="success" dot>Published</x-badge> --}}

@props([
    'variant' => 'soft',
    'color' => 'brand',
    'size' => 'md',
    'radius' => 'full',
    'dot' => false,
    'leading' => null,
    'trailing' => null,
    'as' => 'span',
])

{{-- Styles --}}
@base('badge', 'tone-colors inline-flex max-w-full shrink-0 items-center justify-center gap-1.5 border align-middle font-medium leading-none whitespace-nowrap')
@base('content', 'min-w-0 truncate')
@base('dot', 'size-1.5 shrink-0 rounded-full bg-current')
@base('leading', 'inline-flex shrink-0 items-center [&>svg]:size-3.5')
@base('trailing', 'inline-flex shrink-0 items-center [&>svg]:size-3.5')
@cases('variant', [
    'solid' => ['badge' => ['border-transparent bg-(--tone) text-(--tone-on)']],
    'soft' => ['badge' => ['border-transparent bg-(--tone-soft) text-(--tone-ink)']],
    'surface' => ['badge' => ['border-(--tone-line) bg-(--tone-surface) text-(--tone-ink)']],
    'outline' => ['badge' => ['border-(--tone-line) bg-transparent text-(--tone-ink)']],
    'ghost' => ['badge' => ['border-transparent bg-transparent text-(--tone-ink)']],
])
@cases('color', [
    'brand' => ['badge' => ['tone-brand']],
    'neutral' => ['badge' => ['tone-neutral']],
    'contrast' => ['badge' => ['tone-contrast']],
    'success' => ['badge' => ['tone-success']],
    'info' => ['badge' => ['tone-info']],
    'warning' => ['badge' => ['tone-warning']],
    'danger' => ['badge' => ['tone-danger']],
])
@size('badge', ['sm' => 'h-6 px-2 text-xs', 'md' => 'h-7 px-2.5 text-sm', 'lg' => 'h-8 px-3 text-sm'])
@option('radius', 'badge', [
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

{{-- Markup --}}
<x-malevich::primitive :as="$as" @attributes('badge', slot: $attributes)>
    @if ($dot)
        <span @attributes('dot') aria-hidden="true"></span>
    @endif
    @if (filled($leading))
        <span @attributes('leading')>{{ $leading }}</span>
    @endif
    <span @attributes('content')>{{ $slot }}</span>
    @if (filled($trailing))
        <span @attributes('trailing')>{{ $trailing }}</span>
    @endif
</x-malevich::primitive>
