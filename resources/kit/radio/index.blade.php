{{-- <x-radio name="plan" value="pro" label="Professional plan" description="For growing teams" /> --}}

@props([
    'color' => 'brand',
    'variant' => 'solid',
    'size' => 'md',
    'label' => null,
    'description' => null,
    'error' => null,
    'invalid' => false,
    'checked' => false,
    'disabled' => false,
    'required' => false,
])

{{-- State --}}
@php
    $controlAttributes = $attributes->except(['class', 'style']);
    $id = $controlAttributes->get('id') ?? 'radio-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
    $name = $controlAttributes->get('name');
    $errorKey = $name ? rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;
    $error = $error ?? ($errorKey && isset($errors) && $errors->has($errorKey) ? $errors->first($errorKey) : null);
    $invalid = $invalid || ($error !== null && $error !== false && $error !== '');
    $caption = $label ?? ($slot->hasActualContent() ? $slot : null);
    $hasError = $error !== true && filled($error);
    $describedBy = trim(implode(' ', array_filter([
        $controlAttributes->get('aria-describedby'),
        filled($description) ? $id.'-description' : null,
        $hasError ? $id.'-error' : null,
    ])));
@endphp

{{-- Styles --}}
@base('radio', [
    'tone-colors relative inline-flex items-start gap-2.5 align-middle text-sm leading-5',
    'cursor-pointer select-none' => !$disabled,
    'cursor-not-allowed opacity-50' => $disabled,
])
@base('control', 'peer sr-only')
@base('indicator', [
    'choice-indicator relative inline-flex shrink-0 items-center justify-center border border-line bg-surface-raised',
    'transition-[background-color,border-color,box-shadow] duration-150 motion-reduce:transition-none',
    'peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-(--tone-ink)',
    'border-danger' => $invalid,
    'rounded-full',
])
@base('content', 'flex min-w-0 flex-col gap-0.5')
@base('label', 'font-medium text-ink')
@base('description', 'text-sm leading-6 text-ink-muted')
@base('error', 'text-sm leading-6 text-danger-ink')
@cases('color', [
    'brand' => ['radio' => ['tone-brand']],
    'neutral' => ['radio' => ['tone-neutral']],
    'contrast' => ['radio' => ['tone-contrast']],
    'success' => ['radio' => ['tone-success']],
    'info' => ['radio' => ['tone-info']],
    'warning' => ['radio' => ['tone-warning']],
    'danger' => ['radio' => ['tone-danger']],
])
@cases('variant', [
    'solid' => ['indicator' => ['peer-checked:bg-(--tone-fill) peer-checked:text-(--tone-fill-on) peer-checked:border-transparent']],
    'soft' => ['indicator' => ['peer-checked:bg-(--tone-soft) peer-checked:text-(--tone-ink) peer-checked:border-(--tone-line)']],
])
@size('indicator', ['sm' => 'mt-0.5 size-4', 'md' => 'mt-px size-4.5', 'lg' => 'mt-0 size-5'])
@base('indicator', 'peer-checked:[&>svg]:opacity-100')
@base('mark', 'size-[75%] opacity-0 transition-opacity duration-150 motion-reduce:transition-none')

{{-- Markup --}}
<label @attributes('radio', slot: $attributes->only(['class', 'style']), merge: ['for' => $id, 'data-invalid' => $invalid ? '' : null])>
    <input @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'type' => 'radio', 'value' => 'on', 'checked' => $checked, 'disabled' => $disabled, 'required' => $required], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) />
    <span @attributes('indicator') aria-hidden="true">
        <svg @attributes('mark') viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="4" /></svg>
    </span>
    @if (filled($caption) || filled($description) || $hasError)
        <span @attributes('content')>
            @if (filled($caption))
                <span @attributes('label')>{{ $caption }}</span>
            @endif
            @if (filled($description))
                <span @attributes('description', merge: ['id' => $id.'-description'])>{{ $description }}</span>
            @endif
            @if ($hasError)
                <span @attributes('error', merge: ['id' => $id.'-error'])>{{ $error }}</span>
            @endif
        </span>
    @endif
</label>
