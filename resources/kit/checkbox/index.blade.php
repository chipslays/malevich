{{-- <x-checkbox name="notifications" label="Enable notifications" description="Receive important account updates" /> --}}

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
    'indeterminate' => false,
    'nonce' => null,
])

{{-- State --}}
@php
    $controlAttributes = $attributes->except(['class', 'style']);
    $id = $controlAttributes->get('id') ?? 'checkbox-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
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
@base('checkbox', [
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
    'rounded-sm',
])
@base('content', 'flex min-w-0 flex-col gap-0.5')
@base('label', 'font-medium text-ink')
@base('description', 'text-sm leading-6 text-ink-muted')
@base('error', 'text-sm leading-6 text-danger-ink')
@cases('color', [
    'brand' => ['checkbox' => ['tone-brand']],
    'neutral' => ['checkbox' => ['tone-neutral']],
    'contrast' => ['checkbox' => ['tone-contrast']],
    'success' => ['checkbox' => ['tone-success']],
    'info' => ['checkbox' => ['tone-info']],
    'warning' => ['checkbox' => ['tone-warning']],
    'danger' => ['checkbox' => ['tone-danger']],
])
@cases('variant', [
    'solid' => ['indicator' => ['peer-checked:bg-(--tone-fill) peer-checked:text-(--tone-fill-on) peer-checked:border-transparent']],
    'soft' => ['indicator' => ['peer-checked:bg-(--tone-soft) peer-checked:text-(--tone-ink) peer-checked:border-(--tone-line)']],
])
@size('indicator', ['sm' => 'mt-0.5 size-4', 'md' => 'mt-px size-4.5', 'lg' => 'mt-0 size-5'])
@base('indicator', 'peer-checked:[&>svg]:opacity-100')
@base('mark', 'size-[75%] opacity-0 transition-opacity duration-150 motion-reduce:transition-none')
@base('indicator', 'peer-indeterminate:bg-(--tone-fill) peer-indeterminate:border-transparent peer-indeterminate:text-(--tone-fill-on) peer-indeterminate:[&>.mixed]:opacity-100 peer-indeterminate:[&>.check]:opacity-0')

{{-- Markup --}}
<label @attributes('checkbox', slot: $attributes->only(['class', 'style']), merge: ['for' => $id, 'data-invalid' => $invalid ? '' : null])>
    <input @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'type' => 'checkbox', 'value' => 'on', 'checked' => $checked, 'disabled' => $disabled, 'required' => $required], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) />
    <span @attributes('indicator') aria-hidden="true">
        <svg @attributes('mark', merge: ['class' => 'check']) viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="m3.5 8 3 3 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
        <svg @attributes('mark', merge: ['class' => 'mixed absolute']) viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h8" stroke-linecap="round" /></svg>
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
    @if ($indeterminate)
        <script @if ($nonce) nonce="{{ $nonce }}" @endif>
            { const control = document.currentScript.closest('label').querySelector('input[type="checkbox"]'); control.indeterminate = true; }
        </script>
    @endif
</label>
