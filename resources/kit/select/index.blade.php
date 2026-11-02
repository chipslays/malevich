{{-- <x-select name="country"><option value="">Choose a country</option><option value="ru">Russia</option><option value="us">United States</option></x-select> --}}

@props([
    'variant' => 'outline',
    'color' => 'brand',
    'size' => 'md',
    'radius' => 'brand',
    'label' => null,
    'description' => null,
    'error' => null,
    'invalid' => false,
    'disabled' => false,
    'required' => false,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'multiple' => false,
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
    $selectedValues = array_map('strval', (array) $value);
@endphp

{{-- Styles --}}
@base('field', '')
@base('select', ['tone-colors control-frame relative flex w-full min-w-0 items-center border text-ink',
    'transition-[background-color,border-color,box-shadow] duration-150 motion-reduce:transition-none',
    'data-disabled:cursor-not-allowed data-disabled:opacity-50', 'select-multiple' => $multiple])
@base('control', [
    'w-full min-w-0 appearance-none border-0 bg-transparent p-0 [font-family:inherit] text-inherit outline-none disabled:cursor-not-allowed',
    'pe-6' => !$multiple,
    '[&>option]:bg-surface-raised [&>option]:text-ink [&>optgroup]:bg-surface-raised [&>optgroup]:text-ink',
])
@base('indicator', 'pointer-events-none absolute end-3 size-4 text-ink-muted')
@cases('variant', [
    'outline' => ['select' => ['bg-surface-raised border-line']],
    'soft' => ['select' => ['bg-(--tone-soft) border-transparent']],
    'surface' => ['select' => ['bg-(--tone-surface) border-(--tone-line)']],
    'ghost' => ['select' => ['bg-transparent border-transparent shadow-none']],
])
@cases('color', [
    'brand' => ['select' => ['tone-brand']],
    'neutral' => ['select' => ['tone-neutral']],
    'contrast' => ['select' => ['tone-contrast']],
    'success' => ['select' => ['tone-success']],
    'info' => ['select' => ['tone-info']],
    'warning' => ['select' => ['tone-warning']],
    'danger' => ['select' => ['tone-danger']],
])
@size('select', [
    'xs' => 'h-7 px-2 text-xs gap-1.5',
    'sm' => 'h-8 px-3 text-sm gap-2',
    'md' => 'h-9 px-3 text-sm gap-2',
    'lg' => 'h-10 px-3.5 text-base gap-2.5',
    'xl' => 'h-11 px-4 text-base gap-3',
])
@option('radius', 'select', [
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
@compound(['invalid' => true], targets: ['select' => ['control-invalid']])

{{-- Markup --}}
<x-dynamic-component @attributes('field') :component="$fieldComponent" :label="$label" :description="$description" :error="$error" :for="$id" :required="$required">
    <div @attributes('select', slot: $attributes->only(['class', 'style']), merge: ['data-disabled' => $disabled ? '' : null, 'data-invalid' => $invalid ? '' : null])>
        <select @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'disabled' => $disabled, 'required' => $required, 'multiple' => $multiple], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null])>
            @if ($placeholder !== null && !$multiple)
                <option value="" @selected($value === null || $value === '') @disabled($required)>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                @if (is_array($optionLabel))
                    <optgroup label="{{ $optionValue }}">
                        @foreach ($optionLabel as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected(in_array((string) $groupValue, $selectedValues, true))>{{ $groupLabel }}</option>@endforeach
                    </optgroup>
                @else
                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>{{ $optionLabel }}</option>
                @endif
            @endforeach
            {{ $slot }}
        </select>
        @unless ($multiple)
            <svg @attributes('indicator') viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" /></svg>
        @endunless
    </div>
</x-dynamic-component>
