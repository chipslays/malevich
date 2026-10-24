{{-- <x-field label="Country" for="country" description="Used for shipping."><select id="country" name="country">…</select></x-field> --}}

{{-- Props --}}

@props([
    'label' => null,
    'description' => null,
    // A message, or true to only mark the field. Taken from $errors by `name` when omitted.
    'error' => null,
    'required' => false,

    // The control's id: the label points at it, the description and error ids derive from it.
    'for' => null,
    // The validated input name, for looking up its error: user.email or user[email].
    'name' => null,

    // A group of controls (radios, checkboxes) is a fieldset with a legend.
    'as' => 'div',
])

{{-- State --}}

@php
    $filled = fn ($value) => $value instanceof \Illuminate\View\ComponentSlot
        ? $value->hasActualContent()
        : $value !== null && $value !== false && trim((string) $value) !== '';

    $errorKey = $name ? rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;

    if ($error === null && $errorKey && isset($errors) && $errors->has($errorKey)) {
        $error = $errors->first($errorKey);
    }

    $hasLabel = $filled($label);
    $hasDescription = $filled($description);
    $hasErrorMessage = $error !== true && $filled($error);

    $group = $as === 'fieldset';
    $id = $for ?? 'field-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
@endphp

{{-- Styles --}}

@base(['flex w-full min-w-0 flex-col gap-1.5', 'm-0 border-0 p-0' => $group])
@base('label', ['text-sm font-medium text-gray-950 select-none dark:text-gray-100', 'p-0' => $group])
@base('required', 'ms-0.5 text-danger-600 dark:text-danger-400')
@base('description', 'text-sm text-gray-500 dark:text-gray-400')
@base('error', 'text-sm text-danger-700 dark:text-danger-400')

{{-- Markup --}}

<x-malevich::primitive :as="$as" @attributes(merge: ['data-invalid' => $error === true || $hasErrorMessage ? '' : null, 'aria-describedby' => $group ? (collect([$hasDescription ? "{$id}-description" : null, $hasErrorMessage ? "{$id}-error" : null])->filter()->implode(' ') ?: null) : null])>
    @if ($hasLabel)
        @if ($group)
            <legend @attributes('label')>
        @else
            <label @attributes('label', merge: ['for' => $for])>
        @endif
            {{ $label }}

            @if ($required)
                <span @attributes('required', merge: ['aria-hidden' => 'true'])>*</span>
            @endif
        @if ($group)
            </legend>
        @else
            </label>
        @endif
    @endif

    {{ $slot }}

    @if ($hasDescription)
        <p @attributes('description', merge: ['id' => "{$id}-description"])>{{ $description }}</p>
    @endif

    @if ($hasErrorMessage)
        <p @attributes('error', merge: ['id' => "{$id}-error"])>{{ $error }}</p>
    @endif
</x-malevich::primitive>
