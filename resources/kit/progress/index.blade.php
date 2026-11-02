{{-- <x-progress :value="65" label="Uploading" show-value /> --}}

@props([
    'value' => 0,
    'max' => 100,
    'color' => 'brand',
    'variant' => 'solid',
    'size' => 'md',
    'label' => null,
    'showValue' => false,
    'indeterminate' => false,
])

{{-- State --}}
@php
    $maximum = is_numeric($max) && is_finite((float) $max) && (float) $max > 0 ? (float) $max : 100;
    $current = is_numeric($value) && is_finite((float) $value) ? max(0, min($maximum, (float) $value)) : 0;
    $percent = (int) round($current / $maximum * 100);
    $id = $attributes->get('id') ?? 'progress-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
@endphp

{{-- Styles --}}
@base('progress', 'tone-colors flex w-full min-w-0 flex-col gap-2')
@base('heading', 'flex items-center justify-between gap-3 text-sm leading-6')
@base('label', 'font-medium text-ink')
@base('value', 'ms-auto tabular-nums text-ink-muted')
@base('track', 'progress-track block w-full overflow-hidden rounded-full border-0')
@cases('variant', [
    'solid' => ['track' => ['[--progress-fill:var(--tone-fill)]']],
    'soft' => ['track' => ['[--progress-fill:color-mix(in_oklab,var(--tone-fill)_65%,var(--surface-raised))]']],
    'striped' => ['track' => ['progress-striped [--progress-fill:var(--tone-fill)]']],
])
@cases('color', [
    'brand' => ['progress' => ['tone-brand']],
    'neutral' => ['progress' => ['tone-neutral']],
    'contrast' => ['progress' => ['tone-contrast']],
    'success' => ['progress' => ['tone-success']],
    'info' => ['progress' => ['tone-info']],
    'warning' => ['progress' => ['tone-warning']],
    'danger' => ['progress' => ['tone-danger']],
])
@size('track', ['sm' => 'h-1', 'md' => 'h-1.5', 'lg' => 'h-2.5'])

{{-- Markup --}}
<div @attributes('progress', slot: $attributes->only(['class', 'style']))>
    @if (filled($label) || ($showValue && !$indeterminate))
        <div @attributes('heading')>
            @if (filled($label))
                <label @attributes('label', merge: ['for' => $id])>{{ $label }}</label>
            @endif
            @if ($showValue && !$indeterminate)
                <span @attributes('value')>{{ $percent }}%</span>
            @endif
        </div>
    @endif
    <progress @attributes('track', slot: $attributes->except(['class', 'style']), merge: ['id' => $id, 'max' => $maximum, 'value' => $indeterminate ? null : $current])>{{ $indeterminate ? '' : $percent.'%' }}</progress>
</div>
