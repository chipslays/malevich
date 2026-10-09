@props(['variant' => 'teal', 'tall' => false])

@base('flex')
@base('title', 'font-medium')

@cases('variant', [
    'teal' => ['default' => 'bg-teal', 'title' => 'text-teal-100', 'glow' => 'glow-teal'],
    'graphite' => ['default' => 'bg-graphite', 'title' => 'text-white'],
])

<div @ui>
    <span @ui('title')>{{ $slot }}</span>
    @has('glow')<i @ui('glow')></i>@else<b>no glow</b>@endhas
</div>
