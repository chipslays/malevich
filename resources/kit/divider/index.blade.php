{{-- <x-divider>Continue with</x-divider> --}}

@props([
    'orientation' => 'horizontal',
    'align' => 'center',
    'variant' => 'solid',
    'color' => 'neutral',
])

{{-- State --}}
@php($hasText = $slot->hasActualContent())

{{-- Styles --}}
@base('divider', 'tone-colors flex items-center gap-4 text-sm leading-6 text-ink-muted')
@base('before', ['min-w-0 border-(--tone-line)', 'flex-1' => !$hasText])
@base('after', 'min-w-0 border-(--tone-line)')
@base('text', 'shrink-0 font-medium')
@cases('orientation', [
    'horizontal' => ['divider' => ['w-full flex-row'], 'before' => ['border-t'], 'after' => ['border-t']],
    'vertical' => ['divider' => ['min-h-8 self-stretch flex-col'], 'before' => ['min-h-3 border-s'], 'after' => ['min-h-3 border-s']],
])
@cases('align', [
    'start' => ['before' => ['basis-4 shrink-0'], 'after' => ['flex-1']],
    'center' => ['before' => ['flex-1'], 'after' => ['flex-1']],
    'end' => ['before' => ['flex-1'], 'after' => ['basis-4 shrink-0']],
])
@cases('variant', [
    'solid' => ['before' => ['border-solid'], 'after' => ['border-solid']],
    'dashed' => ['before' => ['border-dashed'], 'after' => ['border-dashed']],
    'dotted' => ['before' => ['border-dotted'], 'after' => ['border-dotted']],
])
@cases('color', [
    'brand' => ['divider' => ['tone-brand']],
    'neutral' => ['divider' => ['tone-neutral']],
    'contrast' => ['divider' => ['tone-contrast']],
    'success' => ['divider' => ['tone-success']],
    'info' => ['divider' => ['tone-info']],
    'warning' => ['divider' => ['tone-warning']],
    'danger' => ['divider' => ['tone-danger']],
])

{{-- Markup --}}
<div @attributes('divider', slot: $attributes, merge: ['role' => 'separator', 'aria-orientation' => $orientation])>
    <span @attributes('before') aria-hidden="true"></span>
    @if ($hasText)
        <span @attributes('text')>{{ $slot }}</span>
        <span @attributes('after') aria-hidden="true"></span>
    @endif
</div>
