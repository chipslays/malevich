@props(['size' => 'xs'])

@size(['xs' => 'root-xs', 'xl' => 'root-xl'])
@size('icon', ['xs' => 'icon-xs', 'xl' => 'icon-xl'])
@compound('icon', ['size' => 'xl'], 'large-icon')

<button @attributes>
    <i @attributes('icon')></i>
    <b>{{ $attributes->for('icon')->get('trailing') }}</b>
    <em>{{ json_encode($attributes->for('icon')->modifiers('trailing')) }}</em>
</button>
