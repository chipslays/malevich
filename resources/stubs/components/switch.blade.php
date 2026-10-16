{{--
    <x-switch checked />
--}}

@props(['checked' => false])

@base('relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors')
@base('thumb', 'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform')

@option('checked', [
    'true' => 'bg-green-500',
    'false' => 'bg-zinc-300',
])

@option('checked', 'thumb', [
    'true' => 'translate-x-5',
])

<span @attributes(merge: ['role' => 'switch', 'aria-checked' => $checked ? 'true' : 'false'])>
    <span @attributes('thumb')></span>
</span>
