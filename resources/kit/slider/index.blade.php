{{-- <x-slider name="volume" label="Volume" :value="40" show-value suffix="%" /> --}}

@props([
    'value' => 0,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'color' => 'brand',
    'variant' => 'solid',
    'size' => 'md',
    'label' => null,
    'description' => null,
    'error' => null,
    'invalid' => false,
    'disabled' => false,
    'required' => false,
    'showValue' => false,
    'suffix' => '',
    'nonce' => null,
])

{{-- State --}}
@php
    $controlAttributes = $attributes->except(['class', 'style']);
    $id = $controlAttributes->get('id') ?? 'control-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
    $name = $controlAttributes->get('name');
    $errorKey = $name ? rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;
    $error = $error ?? ($errorKey && isset($errors) && $errors->has($errorKey) ? $errors->first($errorKey) : null);
    $invalid = $invalid || ($error !== null && $error !== false && $error !== '');
    $describedBy = trim(implode(' ', array_filter([
        $controlAttributes->get('aria-describedby'),
        filled($description) ? $id.'-description' : null,
        $error !== true && filled($error) ? $id.'-error' : null,
    ])));
    $fieldComponent = substr(\Malevich\Malevich::componentTag('field'), 2);
    $minimum = is_numeric($min) && is_finite((float) $min) ? (float) $min : 0;
    $maximum = is_numeric($max) && is_finite((float) $max) ? (float) $max : 100;
    if ($maximum <= $minimum) {
        $minimum = 0;
        $maximum = 100;
    }
    $current = is_numeric($value) && is_finite((float) $value) ? max($minimum, min($maximum, (float) $value)) : $minimum;
    $increment = $step === 'any' ? 'any' : (is_numeric($step) && is_finite((float) $step) && (float) $step > 0 ? $step : 1);
    if ($increment !== 'any') {
        $steps = min(round(($current - $minimum) / (float) $increment), floor(($maximum - $minimum) / (float) $increment + 1e-9));
        $current = max($minimum, min($maximum, $minimum + $steps * (float) $increment));
    }
    $percent = ($current - $minimum) / ($maximum - $minimum) * 100;
@endphp

{{-- Styles --}}
@base('field', '')
@base('slider', ['tone-colors flex w-full min-w-0 items-center py-1', 'opacity-50' => $disabled])
@base('control', 'slider-control block w-full min-w-0 cursor-pointer disabled:cursor-not-allowed focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--tone-ink) rounded-full')
@base('value', 'text-sm tabular-nums text-ink-muted')
@cases('color', [
    'brand' => ['slider' => ['tone-brand']],
    'neutral' => ['slider' => ['tone-neutral']],
    'contrast' => ['slider' => ['tone-contrast']],
    'success' => ['slider' => ['tone-success']],
    'info' => ['slider' => ['tone-info']],
    'warning' => ['slider' => ['tone-warning']],
    'danger' => ['slider' => ['tone-danger']],
])
@cases('variant', [
    'solid' => ['slider' => ['[--slider-color:var(--tone-fill)]']],
    'soft' => ['slider' => ['[--slider-color:color-mix(in_oklab,var(--tone-fill)_65%,var(--surface-raised))]']],
])
@size('slider', [
    'sm' => '[--slider-height:0.1875rem] [--slider-thumb:0.875rem] [--slider-hit:1.5rem]',
    'md' => '[--slider-height:0.25rem] [--slider-thumb:1.125rem] [--slider-hit:1.75rem]',
    'lg' => '[--slider-height:0.375rem] [--slider-thumb:1.25rem] [--slider-hit:2rem]',
])
@compound(['invalid' => true], targets: ['slider' => ['slider-invalid']])

{{-- Markup --}}
<x-dynamic-component @attributes('field') :component="$fieldComponent" :label="$label" :description="$description" :error="$error" :for="$id">
    @if ($showValue)
        <x-slot:trailing>
            <output @attributes('value', merge: ['id' => $id.'-value', 'for' => $id, 'data-slider-suffix' => $suffix])>{{ $current }}{{ $suffix }}</output>
        </x-slot:trailing>
    @endif
    <div @attributes('slider', slot: $attributes->only(['class', 'style']))>
        <input @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'type' => 'range', 'min' => $minimum, 'max' => $maximum, 'step' => $increment, 'value' => $current, 'disabled' => $disabled, 'required' => $required, 'data-malevich-slider' => '', 'style' => '--slider-fill:'.$percent.'%'], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) />
    </div>
</x-dynamic-component>

{{-- Native events stay intact; only the painted fill and optional output change. --}}
@once
    <script @if ($nonce) nonce="{{ $nonce }}" @endif>
        (() => {
            const paint = (control) => {
                const min = Number(control.min);
                const max = Number(control.max);
                const value = Number(control.value);
                control.style.setProperty('--slider-fill', ((value - min) / (max - min) * 100) + '%');
                const output = document.getElementById(control.id + '-value');
                if (output && output.htmlFor.value === control.id) output.textContent = control.value + (output.dataset.sliderSuffix || '');
            };
            document.addEventListener('input', (event) => {
                if (event.target.matches?.('input[data-malevich-slider]')) paint(event.target);
            });
            document.addEventListener('reset', (event) => {
                queueMicrotask(() => event.target.querySelectorAll('input[data-malevich-slider]').forEach(paint));
            });
        })();
    </script>
@endonce
