@props(['extra' => 'dyn'])

@base('chip', 'base-chip')
@base('last', 'base-last')

<div @attributes>
    <span @attributes('chip') class="from-tag {{ $extra }}"></span>
    <span class="before" @attributes('last')></span>
    <i @attributes('chip') class='single'></i>
</div>
