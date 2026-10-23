@props(['busy' => false])

@base('root')

<div @attributes(override: ['aria-busy' => $busy ? 'true' : null, 'role' => 'status', 'title' => false, 'data-note' => '<b>'])></div>
