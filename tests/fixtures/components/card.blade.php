@props(['tone' => null])

@variant(['filled' => 'shadow', 'flat' => 'border'])
@option('tone', ['red' => 'bg-red-50', 'blue' => 'bg-blue-50'])

@base('heading', 'font-bold')

<div @attributes>
    <h2 @attributes('heading', slot: $title)>{{ $title }}</h2>
    {{ $slot }}
</div>
