@props(['variant' => 'primary'])

@preset('ghost', ['variant' => 'ghost'])

@variant(['primary' => 'bg-black', 'ghost' => 'bg-transparent'])

<button @attributes>{{ $slot }}</button>
