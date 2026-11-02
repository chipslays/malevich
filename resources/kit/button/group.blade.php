{{-- <x-button.group aria-label="Actions"><x-button color="neutral" variant="outline">Previous</x-button><x-button color="neutral" variant="outline">Next</x-button></x-button.group> --}}

@props([
    'orientation' => 'horizontal',
    'block' => false,
    'as' => 'div',
])

@base('group', [
    'isolate inline-flex items-stretch align-middle',
    '[&>:is(button,a,[role=button])]:relative',
    '[&>:is(button,a,[role=button])]:active:translate-y-0',
    '[&>:is(button,a,[role=button]):hover]:z-10',
    '[&>:is(button,a,[role=button]):focus-visible]:z-20',
])

@option('orientation', 'group', [
    'horizontal' => [
        'flex-row',
        '[&>:is(button,a,[role=button]):not(:first-child)]:-ms-px',
        '[&>:is(button,a,[role=button]):not(:first-child)]:rounded-s-none',
        '[&>:is(button,a,[role=button]):not(:last-child)]:rounded-e-none',
        '[&>:is(button,a,[role=button]):not(:first-child)]:border-s-[color:color-mix(in_oklab,currentColor_18%,transparent)]',
    ],
    'vertical' => [
        'flex-col',
        '[&>:is(button,a,[role=button]):not(:first-child)]:-mt-px',
        '[&>:is(button,a,[role=button]):not(:first-child)]:rounded-t-none',
        '[&>:is(button,a,[role=button]):not(:last-child)]:rounded-b-none',
        '[&>:is(button,a,[role=button]):not(:first-child)]:border-t-[color:color-mix(in_oklab,currentColor_18%,transparent)]',
    ],
])

@compound(['block' => true], targets: ['group' => [
    'flex w-full',
    '[&>:is(button,a,[role=button])]:min-w-0',
    '[&>:is(button,a,[role=button])]:flex-1' => $orientation === 'horizontal',
]])

<x-malevich::primitive :as="$as" @attributes('group', slot: $attributes, merge: ['role' => 'group', 'data-orientation' => $orientation])>
    {{ $slot }}
</x-malevich::primitive>
