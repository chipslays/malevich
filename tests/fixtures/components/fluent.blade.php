@props(['size' => 'md'])

@size(['md' => 'p-4'])
@color('icon', ['red' => 'text-red-500'])

<div {{ $attributes->size($size)->only('class') }}>
    <i {{ $attributes->for('icon')->color('red')->merge(['aria-hidden' => 'true']) }}></i>
</div>
