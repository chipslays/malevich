@props(['variant' => 'teal', 'tall' => false])

@base('flex')
@base('title', 'font-medium')

@cases('variant', [
    'teal' => ['default' => 'bg-teal', 'title' => 'text-teal-100', 'glow' => 'glow-teal'],
    'graphite' => ['default' => 'bg-graphite', 'title' => 'text-white'],
])

<div @attributes>
    <span @attributes('title')>{{ $slot }}</span>
    @styled('glow')<i @attributes('glow')></i>@else<b>no glow</b>@endstyled
</div>
