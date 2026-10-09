{{--
    <x-stat variant="teal" title="Meters" :value="12" unit="pcs">18 devices</x-stat>
    <x-stat variant="graphite" title="Documents" :value="67">+2 this month</x-stat>
--}}

@props([
    'variant' => 'teal',
    'title',
    'value',
    'unit' => null,
])

@base('relative flex flex-col gap-4 overflow-hidden rounded-2xl bg-gradient-to-br p-4 text-white')
@base('glow', 'pointer-events-none absolute rounded-full blur-3xl')
@base('head', 'flex items-center gap-2.5')
@base('title', 'font-medium')
@base('value', 'text-5xl font-bold leading-none tracking-tight')
@base('footer', 'mt-auto flex items-center gap-2 border-t pt-3')

{{--
    One option, many elements: @cases groups the maps by value, so "what does teal look like"
    is answered in one place. `default` is the main element, other keys are targets.
--}}
@cases('variant', [
    'teal' => [
        'default' => 'from-teal-600 to-teal-800',
        'glow' => '-bottom-16 -left-10 size-40 bg-lime-400/20',
        'title' => 'text-teal-100',
        'footer' => 'border-white/15 text-teal-100',
        // Only teal has this part: no @base for it, and @has below skips it elsewhere.
        'halo' => 'pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-teal-300/30 blur-3xl',
    ],
    'graphite' => [
        'default' => 'from-gray-800 to-gray-950',
        'glow' => '-bottom-16 -right-10 size-44 bg-lime-500/25',
        'title' => 'text-white/80',
        'footer' => 'border-white/10 text-white/70',
    ],
])

<div @ui>
    <div @ui('glow')></div>
    @has('halo')<div @ui('halo')></div>@endhas

    <div @ui('head')>
        <span @ui('title')>{{ $title }}</span>
    </div>

    <div class="flex items-baseline gap-2">
        <span @ui('value')>{{ $value }}</span>
        @if ($unit)<span class="text-base opacity-70">{{ $unit }}</span>@endif
    </div>

    <div @ui('footer')>{{ $slot }}</div>
</div>
