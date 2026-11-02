<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') · Malevich</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <script>
        try {
            const theme = localStorage.getItem('atelier-theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches));
        } catch {}
    </script>
    @if (is_file(\Workbench\App\Providers\WorkbenchServiceProvider::cssPath()))
        <link rel="stylesheet" href="{{ route('examples.css') }}">
    @else
        {!! \Malevich\Playground\Playground::head() !!}
    @endif
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; font-size: 14px; line-height: 1.5; }
        .demo-shell { width: calc(100% - 80px); max-width: 1120px; margin-inline: auto; }
        [hidden] { display: none !important; }
        .app-scroll { scrollbar-width: thin; scrollbar-color: var(--line) transparent; }
        [data-density="compact"] main table td { padding-block: 0.75rem; }
        dialog { color-scheme: light; } .dark dialog { color-scheme: dark; }
    </style>
</head>
<body class="min-h-dvh min-w-[880px] bg-surface text-ink antialiased" data-density="{{ session('examples.settings.compact', false) ? 'compact' : 'comfortable' }}">
    @php
        $navigation = [
            ['route' => 'examples.overview', 'label' => 'Обзор'],
            ['route' => 'examples.projects', 'label' => 'Проекты'],
            ['route' => 'examples.settings', 'label' => 'Настройки'],
        ];
    @endphp
    <header class="border-b border-line bg-surface-raised">
        <div class="demo-shell flex h-20 items-center justify-between gap-8">
            <a href="{{ route('examples.overview') }}" class="inline-flex items-center gap-3 text-xl font-semibold tracking-tight">
                <span class="size-5 rounded-[4px] bg-contrast shadow-[6px_-6px_0_-1px_var(--brand)]" aria-hidden="true"></span>
                malevich
            </a>
            <nav class="flex h-full items-center gap-7" aria-label="Основная навигация">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'])) aria-current="page" @endif
                        @class(['inline-flex h-full items-center border-b-2 px-1 text-sm font-medium transition-colors', 'border-contrast text-ink' => request()->routeIs($item['route']), 'border-transparent text-ink-muted hover:text-ink' => !request()->routeIs($item['route'])])>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-4">
                <a href="/malevich" class="text-sm text-ink-muted hover:text-ink">Компоненты</a>
                <x-button variant="ghost" color="contrast" square data-theme-toggle aria-label="Переключить тему" title="Переключить тему">
                    <x-icon name="lucide-sun" class="hidden size-4 shrink-0 dark:block" />
                    <x-icon name="lucide-moon" class="size-4 shrink-0 dark:hidden" />
                </x-button>
                <a href="{{ route('examples.settings') }}" aria-label="Профиль"><x-avatar :name="session('examples.settings.name', 'Алексей Морозов')" size="sm" variant="outline" /></a>
            </div>
        </div>
    </header>
    <main class="demo-shell py-10">
        @if (session('notice'))
            <div data-notice role="status" class="mb-6 flex items-center gap-3 rounded-lg border border-success/20 bg-success/8 px-4 py-3 text-sm text-success-ink">
                <x-icon name="lucide-circle-check" class="size-4 shrink-0" />
                <span class="flex-1">{{ session('notice') }}</span>
                <button type="button" data-dismiss aria-label="Закрыть уведомление"><x-icon name="lucide-x" class="size-4 shrink-0" /></button>
            </div>
        @endif
        @yield('content')
        <footer class="mt-12 flex items-center justify-between border-t border-line pt-6 text-sm text-ink-muted"><span>Примеры интерфейсов · Malevich</span><span>Собрано из компонентов</span></footer>
    </main>
    @yield('dialogs')
    @include('examples::partials.scripts')
</body>
</html>
