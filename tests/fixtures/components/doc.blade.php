@props(['loading' => false, 'size' => 'md'])

@include('partials.sizes')

@base('title', 'font-semibold')

@compound(['loading' => true], 'cursor-wait')
@compound(['loading' => true, 'size' => 'sm'], 'gap-1')

<div @ui><b @ui('title')></b></div>
