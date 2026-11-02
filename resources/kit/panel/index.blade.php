{{-- <x-panel title="Project activity" description="The latest updates from your team">Your content goes here.</x-panel> --}}

@props([
    'title' => null,
    'description' => null,
    'padding' => 'md',
    'as' => 'section',
])

@base('panel', 'flex min-w-0 flex-col rounded-2xl border border-line bg-surface-raised text-ink')
@base('header', 'flex items-start justify-between gap-4 px-5 pt-5 pb-4')
@base('heading', 'min-w-0 space-y-1')
@base('title', 'text-lg font-semibold leading-7')
@base('description', 'text-sm leading-6 text-ink-muted')
@base('action', 'flex shrink-0 items-center gap-2')
@base('body', ['min-w-0', 'pt-0' => filled($title) || filled($description) || isset($action)])
@base('footer', 'px-5 pb-5 text-sm text-ink-muted')
@option('padding', 'body', ['none' => 'p-0', 'sm' => 'p-4', 'md' => 'p-5', 'lg' => 'p-6'])

<x-malevich::primitive :as="$as" @attributes('panel', slot: $attributes)>
    @if (filled($title) || filled($description) || isset($action))
        <header @attributes('header')>
            <div @attributes('heading')>
                @if (filled($title))<h2 @attributes('title')>{{ $title }}</h2>@endif
                @if (filled($description))<p @attributes('description')>{{ $description }}</p>@endif
            </div>
            @isset($action)<div @attributes('action')>{{ $action }}</div>@endisset
        </header>
    @endif
    <div @attributes('body')>{{ $slot }}</div>
    @isset($footer)<footer @attributes('footer')>{{ $footer }}</footer>@endisset
</x-malevich::primitive>
