@props(['extra' => 'dyn'])

@base('chip', 'base-chip')
@base('last', 'base-last')

<div @ui>
    <span @ui('chip') class="from-tag {{ $extra }}"></span>
    <span class="before" @ui('last')></span>
    <i @ui('chip') class='single'></i>
</div>
