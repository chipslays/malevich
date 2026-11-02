{{-- <x-input label="Email" name="email" type="email" placeholder="you@example.com" /> --}}

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
    'readonly' => false,
    'type' => 'text',
    'leading' => null,
    'trailing' => null,
])

{{-- State --}}
@php
    $controlAttributes = $attributes->except(['class', 'style']);
    $id = $controlAttributes->get('id') ?? 'input-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
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
@endphp

{{-- Styles --}}
@base('field', '')
@base('input', ['tone-colors control-frame relative flex w-full min-w-0 items-center border text-ink',
    'transition-[background-color,border-color,box-shadow] duration-150 motion-reduce:transition-none',
    'data-disabled:cursor-not-allowed data-disabled:opacity-50'])
@base('control', 'w-full min-w-0 flex-1 border-0 bg-transparent p-0 [font-family:inherit] text-inherit outline-none placeholder:text-ink-muted/70 disabled:cursor-not-allowed read-only:cursor-default')
@base('leading', 'flex shrink-0 items-center gap-1.5 text-ink-muted [&>svg]:size-4')
@base('trailing', 'flex shrink-0 items-center gap-1.5 text-ink-muted [&>svg]:size-4')
@cases('variant', [
    'outline' => ['input' => ['bg-surface-raised border-line']],
    'soft' => ['input' => ['bg-(--tone-soft) border-transparent']],
    'surface' => ['input' => ['bg-(--tone-surface) border-(--tone-line)']],
    'ghost' => ['input' => ['bg-transparent border-transparent shadow-none']],
])
@cases('color', [
    'brand' => ['input' => ['tone-brand']],
    'neutral' => ['input' => ['tone-neutral']],
    'contrast' => ['input' => ['tone-contrast']],
    'success' => ['input' => ['tone-success']],
    'info' => ['input' => ['tone-info']],
    'warning' => ['input' => ['tone-warning']],
    'danger' => ['input' => ['tone-danger']],
])
@size('input', [
    'xs' => 'h-7 px-2 text-xs gap-1.5',
    'sm' => 'h-8 px-3 text-sm gap-2',
    'md' => 'h-9 px-3 text-sm gap-2',
    'lg' => 'h-10 px-3.5 text-base gap-2.5',
    'xl' => 'h-11 px-4 text-base gap-3',
])
@option('radius', 'input', [
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
@compound(['invalid' => true], targets: ['input' => ['control-invalid']])

{{-- Markup --}}
<x-dynamic-component @attributes('field') :component="$fieldComponent" :label="$label" :description="$description" :error="$error" :for="$id" :required="$required">
    <div @attributes('input', slot: $attributes->only(['class', 'style']), merge: ['data-disabled' => $disabled ? '' : null, 'data-invalid' => $invalid ? '' : null])>
        @if (filled($leading))
            <span @attributes('leading')>{{ $leading }}</span>
        @endif
        <input @attributes('control', slot: $controlAttributes, merge: ['id' => $id, 'type' => $type, 'disabled' => $disabled, 'readonly' => $readonly, 'required' => $required], override: ['aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) />
        @if (filled($trailing))
            <span @attributes('trailing')>{{ $trailing }}</span>
        @endif
    </div>
</x-dynamic-component>
