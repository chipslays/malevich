{{-- <x-dialog id="example-dialog" title="Create a project">Your form goes here.</x-dialog> --}}

@props(['title' => null, 'description' => null, 'size' => 'md'])

@php($id = $attributes->get('id') ?? 'dialog-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)))

@base('dialog', 'm-auto w-[calc(100%-2rem)] max-h-[calc(100dvh-3rem)] overflow-y-auto rounded-2xl border border-line bg-surface-raised p-0 text-ink shadow-2xl backdrop:bg-black/35 backdrop:backdrop-blur-sm')
@base('header', 'flex items-start justify-between gap-4 px-6 pt-6 pb-4')
@base('title', 'text-xl font-semibold tracking-tight')
@base('description', 'mt-1 text-sm leading-6 text-ink-muted')
@base('close', 'inline-flex size-7 items-center justify-center rounded-md text-ink-muted hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus')
@base('body', 'px-6 pb-6')
@base('footer', 'flex items-center justify-end gap-2 border-t border-line px-6 py-4')
@size('dialog', ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'])

<dialog @attributes('dialog', slot: $attributes, merge: ['id' => $id, 'aria-labelledby' => filled($title) ? $id.'-title' : null, 'aria-describedby' => filled($description) ? $id.'-description' : null])>
    <header @attributes('header')>
        <div>
            @if (filled($title))<h2 @attributes('title', merge: ['id' => $id.'-title'])>{{ $title }}</h2>@endif
            @if (filled($description))<p @attributes('description', merge: ['id' => $id.'-description'])>{{ $description }}</p>@endif
        </div>
        <form method="dialog">
            <button @attributes('close') type="submit" aria-label="Закрыть">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m6 6 12 12 M6 18 18 6" /></svg>
            </button>
        </form>
    </header>
    <div @attributes('body')>{{ $slot }}</div>
    @isset($footer)<footer @attributes('footer')>{{ $footer }}</footer>@endisset
</dialog>
