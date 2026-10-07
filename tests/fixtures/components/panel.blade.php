@props(['title' => null, 'footer' => null])

@base('title', 'font-bold')
@base('footer', 'text-xs')

<div>
    <h2 @ui('title')>{{ $title }}</h2>
    <p @ui('footer', slot: false)>{{ $footer }}</p>
</div>
