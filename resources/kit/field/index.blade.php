{{-- <x-field label="Email" for="email" description="Your work email"><input id="email" type="email" class="w-full rounded-brand border border-line bg-surface-raised px-3 h-8" /></x-field> --}}

@props([
    'label' => null,
    'description' => null,
    'error' => null,
    'required' => false,
    'for' => null,
    'name' => null,
    'layout' => 'stacked',
    'as' => 'div',
])

{{-- State --}}
@php
    $has = fn ($value) => $value instanceof \Illuminate\View\ComponentSlot
        ? $value->hasActualContent()
        : $value !== null && $value !== false && trim((string) $value) !== '';
    $errorKey = $name ? rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;
    $error = $error ?? ($errorKey && isset($errors) && $errors->has($errorKey) ? $errors->first($errorKey) : null);
    $hasLabel = $has($label);
    $hasDescription = $has($description);
    $hasError = $error !== true && $has($error);
    $legend = $as === 'fieldset';
    $fieldId = $for ?? 'field-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
    $describedBy = trim(implode(' ', array_filter([$attributes->get('aria-describedby'), $hasDescription ? $fieldId.'-description' : null, $hasError ? $fieldId.'-error' : null])));
@endphp

{{-- Styles --}}
@base('field', ['min-w-0 gap-1.5', 'm-0 min-w-0 border-0 p-0 disabled:opacity-50' => $legend])
@base('heading', 'flex min-w-0 items-center justify-between gap-3')
@base('label', 'text-sm font-medium leading-5 text-ink')
@base('required', 'ms-0.5 text-danger-ink')
@base('body', 'flex min-w-0 flex-col gap-1.5')
@base('description', 'text-sm leading-6 text-ink-muted')
@base('error', 'text-sm leading-6 text-danger-ink')
@base('trailing', 'ms-auto shrink-0 text-xs tabular-nums text-ink-muted')
@cases('layout', [
    'stacked' => ['field' => ['flex flex-col']],
    'inline' => ['field' => ['grid grid-cols-1 items-start gap-x-6 sm:grid-cols-[minmax(8rem,1fr)_minmax(0,2fr)]']],
])

{{-- Markup --}}
<x-malevich::primitive :as="$as" @attributes('field', slot: $attributes, merge: ['data-invalid' => $error !== null && $error !== false && $error !== '' ? '' : null], override: ['aria-describedby' => $legend && $describedBy !== '' ? $describedBy : null])>
    @if ($legend && $hasLabel)
        <legend @attributes('label')>{{ $label }}@if ($required)<span @attributes('required') aria-hidden="true">*</span>@endif</legend>
    @elseif ($hasLabel || isset($trailing))
        <div @attributes('heading')>
            @if ($hasLabel)
                <label @attributes('label', merge: ['for' => $for])>{{ $label }}@if ($required)<span @attributes('required') aria-hidden="true">*</span>@endif</label>
            @endif
            @isset($trailing)
                <span @attributes('trailing')>{{ $trailing }}</span>
            @endisset
        </div>
    @endif
    <div @attributes('body')>
        {{ $slot }}
        @if ($hasDescription)
            <p @attributes('description', merge: ['id' => $fieldId.'-description'])>{{ $description }}</p>
        @endif
        @if ($hasError)
            <p @attributes('error', merge: ['id' => $fieldId.'-error'])>{{ $error }}</p>
        @endif
    </div>
</x-malevich::primitive>
