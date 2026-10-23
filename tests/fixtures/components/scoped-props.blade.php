@props(['iconOnly' => false, 'iconTrailing' => null, 'iconSize' => null, 'loadingLabel' => 'Ready'])

@base('inline-flex')
@size('icon', ['sm' => 'small-icon', 'lg' => 'large-icon'])

<div @attributes>
    <b>{{ json_encode($iconOnly) }}</b>
    <i>{{ $iconTrailing }}</i>
    <label>{{ $loadingLabel }}</label>
    <em>{{ json_encode($attributes->for('icon')->modifiers('trailing')) }}</em>
    <small>{{ $attributes->for('icon')->get('trailing') }}</small>
    <u @attributes('icon')></u>
</div>
