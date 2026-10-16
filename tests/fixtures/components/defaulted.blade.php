@preset('ghost', ['variant' => 'ghost'])
@preset('typo', ['variant' => 'gohst'])

@defaults(['variant' => 'primary', 'size' => 'md'])
@defaults('icon', ['size' => 'sm'])

@variant(['primary' => 'bg-black', 'ghost' => 'bg-transparent'])
@size(['sm' => 'h-8', 'md' => 'h-10'])
@size('icon', ['sm' => 'size-3', 'md' => 'size-4'])

@compound(['variant' => 'primary', 'size' => 'md'], 'shadow')

<button @attributes><svg @attributes('icon')></svg>{{ $slot }}</button>
