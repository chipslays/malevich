@props(['variant' => 'teal', 'size' => 'md'])

@size(['sm' => 'p-2', 'md' => 'p-4'])

@cases('variant', [
    'teal' => ['_' => 'bg-teal', 'title' => 'text-teal-100'],
    'plain' => 'bg-plain',
])

<div @ui>
    <span @ui('title')>{{ $slot }}</span>
</div>
