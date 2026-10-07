<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Malevich playground</title>
    {{-- Tailwind v4 in the browser: no build step for the playground --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
<main class="mx-auto max-w-4xl space-y-12 px-4 py-12">

    <header>
        <h1 class="text-3xl font-bold">Malevich playground</h1>
        <p class="mt-2 text-zinc-600">
            Components live in <code>workbench/resources/views/components</code>.
            Edit them or this page and refresh. Open DevTools to see the rendered classes.
        </p>
    </header>

    @php
        $section = 'space-y-4';
        $row = 'flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-6';
        $code = 'overflow-x-auto rounded-xl bg-zinc-900 p-4 text-sm text-zinc-100';

        $snippets = [
            <<<'BLADE'
<x-button>Primary</x-button>
<x-button variant="outline" size="sm" loading>Saving</x-button>
<x-button class="shadow-lg shadow-zinc-400" onclick="alert('hi')">Extra class + onclick</x-button>
BLADE,
            <<<'BLADE'
<x-badge color="green">Paid</x-badge>
<x-badge variant="solid" color="red">Overdue</x-badge>
BLADE,
            <<<'BLADE'
<x-alert color="red">
    <x-slot:title class="text-lg uppercase">Payment failed</x-slot:title>
    ...
</x-alert>
BLADE,
            <<<'BLADE'
<x-card preset="featured">...</x-card>
<x-card preset="featured" padding="sm">...</x-card>  {{-- explicit prop wins --}}
BLADE,
            <<<'BLADE'
<x-switch />
<x-switch checked />
BLADE,
        ];
    @endphp

    {{-- Button --}}
    <section class="{{ $section }}">
        <h2 class="text-xl font-semibold">Button - @@variant, @@size, a target</h2>
        <div class="{{ $row }}">
            <x-button>Primary</x-button>
            <x-button variant="secondary">Secondary</x-button>
            <x-button variant="outline">Outline</x-button>
            <x-button variant="ghost">Ghost</x-button>
            <x-button variant="danger">Danger</x-button>
        </div>
        <div class="{{ $row }}">
            <x-button size="sm">Small</x-button>
            <x-button>Medium</x-button>
            <x-button size="lg">Large</x-button>
            <x-button size="sm" loading>Saving</x-button>
            <x-button size="lg" variant="outline" loading>Saving</x-button>
            <x-button disabled>Disabled</x-button>
            <x-button variant="outline" href="https://laravel.com" target="_blank">Link (href)</x-button>
            <x-button variant="outline" href="https://laravel.com" disabled>Disabled link</x-button>
            <x-button class="shadow-lg shadow-zinc-400" onclick="alert('hi')">Extra class + onclick</x-button>
        </div>
<pre class="{{ $code }}">{{ $snippets[0] }}</pre>
    </section>

    {{-- Badge --}}
    <section class="{{ $section }}">
        <h2 class="text-xl font-semibold">Badge - @@compound</h2>
        @foreach (['soft', 'solid', 'outline'] as $variant)
            <div class="{{ $row }}">
                @foreach (['gray', 'green', 'red', 'blue'] as $color)
                    <x-badge :variant="$variant" :color="$color">{{ $variant }} {{ $color }}</x-badge>
                @endforeach
            </div>
        @endforeach
<pre class="{{ $code }}">{{ $snippets[1] }}</pre>
    </section>

    {{-- Alert --}}
    <section class="{{ $section }}">
        <h2 class="text-xl font-semibold">Alert - targets and slots</h2>
        <x-alert>A plain informational message.</x-alert>
        <x-alert color="green">
            <x-slot:title>Saved</x-slot:title>
            Your changes have been published.
        </x-alert>
        <x-alert color="red">
            <x-slot:title class="text-lg uppercase">Payment failed</x-slot:title>
            The slot above passes <code>class="text-lg uppercase"</code> to the title.
        </x-alert>
<pre class="{{ $code }}">{{ $snippets[2] }}</pre>
    </section>

    {{-- Card --}}
    <section class="{{ $section }}">
        <h2 class="text-xl font-semibold">Card - @@preset</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-card variant="flat" padding="md">variant="flat" padding="md"</x-card>
            <x-card preset="featured">preset="featured"</x-card>
            <x-card preset="featured" padding="sm">preset="featured" padding="sm"</x-card>
        </div>
<pre class="{{ $code }}">{{ $snippets[3] }}</pre>
    </section>

    {{-- Switch --}}
    <section class="{{ $section }}">
        <h2 class="text-xl font-semibold">Switch - boolean values</h2>
        <div class="{{ $row }}">
            <x-switch />
            <x-switch checked />
        </div>
<pre class="{{ $code }}">{{ $snippets[4] }}</pre>
    </section>

</main>
</body>
</html>
