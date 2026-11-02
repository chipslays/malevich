{{-- <x-switch name="notifications" label="Enable notifications" description="Receive important account updates" /> --}}

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
    $id = $controlAttributes->get('id') ?? 'switch-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
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
@base('switch', [
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
    'brand' => ['switch' => ['tone-brand']],
    'neutral' => ['switch' => ['tone-neutral']],
    'contrast' => ['switch' => ['tone-contrast']],
    'success' => ['switch' => ['tone-success']],
    'info' => ['switch' => ['tone-info']],
    'warning' => ['switch' => ['tone-warning']],
    'danger' => ['switch' => ['tone-danger']],
])
@cases('variant', [
    'solid' => ['indicator' => ['peer-checked:bg-(--tone-fill) peer-checked:text-(--tone-fill-on) peer-checked:border-transparent']],
    'soft' => ['indicator' => ['peer-checked:bg-(--tone-soft) peer-checked:text-(--tone-ink) peer-checked:border-(--tone-line)']],
])
@base('thumb', 'absolute start-0.5 size-(--switch-thumb) rounded-full bg-ink-muted shadow-xs transition-[translate,background-color] duration-150 motion-reduce:transition-none')
@base('indicator', [
    'peer-checked:[&>span]:translate-x-(--switch-travel) rtl:peer-checked:[&>span]:-translate-x-(--switch-travel)',
])
@cases('variant', [
    'solid' => ['indicator' => ['peer-checked:[&>span]:bg-(--tone-fill-on)']],
    'soft' => ['indicator' => ['peer-checked:[&>span]:bg-(--tone-ink)']],
])
@size('indicator', [
    'sm' => 'mt-0.5 h-4 w-7 [--switch-thumb:0.625rem] [--switch-travel:0.75rem]',
    'md' => 'h-5 w-9 [--switch-thumb:0.875rem] [--switch-travel:1rem]',
    'lg' => 'h-6 w-11 [--switch-thumb:1.125rem] [--switch-travel:1.25rem]',
])

{{-- Markup --}}
<label @attributes('switch', slot: $attributes->only(['class', 'style']), merge: ['for' => $id, 'data-invalid' => $invalid ? '' : null])>
    <input @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'type' => 'checkbox', 'value' => 'on', 'checked' => $checked, 'disabled' => $disabled, 'required' => $required, 'role' => 'switch'], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) />
    <span @attributes('indicator') aria-hidden="true">
        <span @attributes('thumb')></span>
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
