{{-- <x-input name="email" type="email" label="Email" placeholder="you@example.com" /> --}}

{{-- Props --}}

@props([
    'variant' => 'outline',
    'color' => 'primary',
    'size' => 'md',
    'radius' => 'brand',

    'label' => null,
    'description' => null,
    // A message, or true for the invalid state without one. Taken from $errors by `name` when omitted.
    'error' => null,
    'required' => false,

    'invalid' => false,
    'disabled' => false,

    'leading' => null,
    'trailing' => null,

    // input:class="..." styles the input itself; plain class styles the outermost element.
    'inputClass' => '',

    'as' => 'input',
])

{{-- State --}}

@php
    $filled = fn ($value) => $value instanceof \Illuminate\View\ComponentSlot
        ? $value->hasActualContent()
        : $value !== null && $value !== false && trim((string) $value) !== '';

    // name="user[email]" is validated as user.email.
    $name = $attributes->get('name');
    $errorKey = $name ? rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.') : null;

    if ($error === null && $errorKey && isset($errors) && $errors->has($errorKey)) {
        $error = $errors->first($errorKey);
    }

    $hasLabel = $filled($label);
    $hasDescription = $filled($description);
    $hasErrorMessage = $error !== true && $filled($error);
    $invalid = $invalid || $error === true || $hasErrorMessage;

    $wrapped = $hasLabel || $hasDescription || $hasErrorMessage;

    // Label, description and error point at the input by id; a bare input needs none.
    $id = $attributes->get('id') ?? ($wrapped ? 'input-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)) : null);
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        $hasDescription ? "{$id}-description" : null,
        $hasErrorMessage ? "{$id}-error" : null,
    ])->filter()->implode(' ') ?: null;

    // Like every input group: layout attributes style the outermost element, everything else reaches the input.
    $outerClass = (string) $attributes->get('class', '');
    $outerStyle = $attributes->get('style');
    $attributes = $attributes->except(['class', 'style', 'id', 'aria-describedby']);
@endphp

{{-- Styles --}}

@base('field', 'flex w-full flex-col gap-1.5')
@base('label', 'text-sm font-medium text-gray-950 select-none dark:text-gray-100')
@base('required', 'ms-0.5 text-danger-600 dark:text-danger-400')
@base('description', 'text-sm text-gray-500 dark:text-gray-400')
@base('error', 'text-sm text-danger-700 dark:text-danger-400')

{{-- The group draws the box; the input is a transparent flex item between the slots. --}}
@base('group', [
    'flex w-full items-center',
    'border',
    'transition-[color,background-color,border-color,box-shadow]',

    'focus-within:outline-2 focus-within:-outline-offset-1',

    'has-disabled:cursor-not-allowed has-disabled:opacity-50',

    // Native validation and the `invalid` prop share the same styling.
    'has-aria-invalid:border-danger-500 has-aria-invalid:focus-within:outline-danger-600',
    'dark:has-aria-invalid:border-danger-400 dark:has-aria-invalid:focus-within:outline-danger-300',
])

@base([
    'h-full min-w-0 flex-1 appearance-none',
    'border-0 bg-transparent p-0 outline-none',
    'text-inherit placeholder:text-gray-400 dark:placeholder:text-gray-500',
    'disabled:cursor-not-allowed',
])

@base('leading', 'flex shrink-0 items-center text-gray-500 dark:text-gray-400')
@base('trailing', 'flex shrink-0 items-center text-gray-500 dark:text-gray-400')

{{-- Size --}}
@cases('size', [
    'xs' => [
        'group' => ['h-7', 'gap-1.5', 'px-2', 'text-xs'],
        'leading,trailing' => ['gap-1', '[&_svg]:size-3.5'],
    ],
    'sm' => [
        'group' => ['h-8', 'gap-2', 'px-2.5', 'text-sm'],
        'leading,trailing' => ['gap-1', '[&_svg]:size-4'],
    ],
    'md' => [
        'group' => ['h-10', 'gap-2', 'px-3', 'text-sm'],
        'leading,trailing' => ['gap-1.5', '[&_svg]:size-4.5'],
    ],
    'lg' => [
        'group' => ['h-11', 'gap-2.5', 'px-3.5', 'text-base'],
        'leading,trailing' => ['gap-1.5', '[&_svg]:size-5'],
    ],
    'xl' => [
        'group' => ['h-12', 'gap-3', 'px-4', 'text-base'],
        'leading,trailing' => ['gap-2', '[&_svg]:size-5.5'],
    ],
])

{{-- Shape --}}
@cases('radius', [
    'brand' => ['group' => 'rounded-brand'],
    'none' => ['group' => 'rounded-none'],
    'xs' => ['group' => 'rounded-xs'],
    'sm' => ['group' => 'rounded-sm'],
    'md' => ['group' => 'rounded-md'],
    'lg' => ['group' => 'rounded-lg'],
    'xl' => ['group' => 'rounded-xl'],
    '2xl' => ['group' => 'rounded-2xl'],
    '3xl' => ['group' => 'rounded-3xl'],
    '4xl' => ['group' => 'rounded-4xl'],
    'full' => ['group' => 'rounded-full'],
    'squircle' => ['group' => ['rounded-2xl', '[corner-shape:squircle]']],
])

{{-- Treatment: color only affects the focus ring --}}
@cases('variant', [
    'outline' => ['group' => [
        'bg-white border-gray-300 text-gray-950 hover:border-gray-400',
        'dark:bg-white/5 dark:border-white/15 dark:text-gray-100 dark:hover:border-white/25',
    ]],
    'soft' => ['group' => [
        'border-transparent bg-gray-950/5 text-gray-950 hover:bg-gray-950/10 focus-within:bg-transparent',
        'dark:bg-gray-400/15 dark:text-gray-100 dark:hover:bg-gray-400/25 dark:focus-within:bg-transparent',
    ]],
    'elevated' => ['group' => [
        'bg-white border-gray-200 text-gray-950 shadow-xs',
        // Raised on dark means lighter than the surface below.
        'dark:bg-white/5 dark:border-white/10 dark:text-gray-100',
    ]],
    'ghost' => ['group' => [
        'border-transparent bg-transparent text-gray-950 hover:bg-gray-950/5',
        'dark:text-gray-100 dark:hover:bg-gray-400/15',
    ]],
])

{{-- Focus ring --}}
@cases('color', [
    'primary' => ['group' => ['focus-within:outline-primary-600', 'dark:focus-within:outline-primary-300']],
    'secondary' => ['group' => ['focus-within:outline-secondary-600', 'dark:focus-within:outline-secondary-300']],
    'accent' => ['group' => ['focus-within:outline-accent-600', 'dark:focus-within:outline-accent-300']],

    'neutral' => ['group' => ['focus-within:outline-gray-600', 'dark:focus-within:outline-gray-300']],
    'contrast' => ['group' => ['focus-within:outline-gray-950', 'dark:focus-within:outline-gray-100']],

    'success' => ['group' => ['focus-within:outline-success-600', 'dark:focus-within:outline-success-300']],
    'warning' => ['group' => ['focus-within:outline-warning-600', 'dark:focus-within:outline-warning-300']],
    'danger' => ['group' => ['focus-within:outline-danger-600', 'dark:focus-within:outline-danger-300']],
    'info' => ['group' => ['focus-within:outline-info-600', 'dark:focus-within:outline-info-300']],
])

{{-- Markup --}}

@if ($wrapped)
    <div @attributes('field', class: $outerClass, merge: ['style' => $outerStyle])>
        @if ($hasLabel)
            <label @attributes('label', merge: ['for' => $id])>
                {{ $label }}

                @if ($required)
                    <span @attributes('required', merge: ['aria-hidden' => 'true'])>*</span>
                @endif
            </label>
        @endif
@endif

<div @attributes('group', class: $wrapped ? '' : $outerClass, merge: ['style' => $wrapped ? null : $outerStyle])>
    @if ($leading !== null)
        <span @attributes('leading')>{{ $leading }}</span>
    @endif

    <x-malevich::primitive :as="$as" :disabled="$disabled" @attributes(class: (string) $inputClass, merge: ['type' => $as === 'input' ? 'text' : null, 'id' => $id, 'required' => $required ? true : null, 'aria-invalid' => $invalid ? 'true' : null, 'aria-describedby' => $describedBy]) />

    @if ($trailing !== null)
        <span @attributes('trailing')>{{ $trailing }}</span>
    @endif
</div>

@if ($wrapped)
        @if ($hasDescription)
            <p @attributes('description', merge: ['id' => "{$id}-description"])>{{ $description }}</p>
        @endif

        @if ($hasErrorMessage)
            <p @attributes('error', merge: ['id' => "{$id}-error"])>{{ $error }}</p>
        @endif
    </div>
@endif
