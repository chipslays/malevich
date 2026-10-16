@props(['title' => null, 'footer' => null])

@base('title', 'font-bold')
@base('footer', 'text-xs')

<div>
    <h2 @attributes('title')>{{ $title }}</h2>
    <p @attributes('footer', slot: false)>{{ $footer }}</p>
</div>
