@props(['preset' => null, 'tone' => null])

@preset('danger', ['variant' => 'filled', 'tone' => 'red'])

@variant(['filled' => 'shadow', 'flat' => 'border'])
@directive('tone', ['red' => 'bg-red-50', 'blue' => 'bg-blue-50'])

@base('heading', 'font-bold')

<div @ui>
    <h2 @ui('heading', slot: $title)>{{ $title }}</h2>
    {{ $slot }}
</div>
