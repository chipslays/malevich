@props(['variant' => 'teal', 'tall' => false])

@base('flex')
@base('title', 'font-medium')

@theme('variant', [
    'teal' => ['default' => 'bg-teal', 'title' => 'text-teal-100', 'glow' => 'glow-teal'],
    'graphite' => ['default' => 'bg-graphite', 'title' => 'text-white'],
])

<div @ui>
    <span @ui('title')>{{ $slot }}</span>
    @hasUi('glow')<i @ui('glow')></i>@else<b>no glow</b>@endif
</div>
