@php
    $relative = function (string $file): string {
        foreach ([base_path(), getcwd() ?: ''] as $root) {
            if ($root !== '' && str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
                return str_replace('\\', '/', substr($file, strlen($root) + 1));
            }
        }

        return str_replace('\\', '/', $file);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Tag names are always written as &lt;x-...: Blade compiles a literal opening x- tag as a component, even inside a PHP string. --}}
    <title>@if ($name)&lt;x-{{ $name }}&gt; · @endif Malevich catalog</title>
    <script>
        try {
            if (localStorage.getItem('malevich.dark') === '1') document.documentElement.classList.add('dark');
            document.documentElement.dataset.mlvStage = localStorage.getItem('malevich.stage') || 'dots';
        } catch (e) {}
    </script>
    {!! $head !!}
    <style>
        .mlv, .mlv *, .mlv *::before, .mlv *::after { box-sizing: border-box; }
        .mlv {
            --bg: #f7f7f8; --panel: #ffffff; --text: #0a0a0b; --muted: #6b6b76; --faint: #a1a1aa;
            --line: #e7e7ea; --line-strong: #d4d4d8; --hover: #f1f1f3; --ink: #0a0a0b; --on-ink: #ffffff;
            --stage: #ffffff; --dot: #e4e4e7; --error-bg: #fef2f2; --error: #b91c1c; --warn-bg: #fffbeb; --warn-line: #fde68a; --warn: #92400e;
            --shadow: 0 1px 2px rgba(10, 10, 11, .04), 0 1px 1px rgba(10, 10, 11, .03);
            --radius: 14px;
            display: grid; grid-template-columns: 264px minmax(0, 1fr); min-height: 100vh;
            background: var(--bg); color: var(--text);
            font-size: 14px; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        /* The catalog's own UI. Previews are left alone: they keep the app's font and styles. */
        .mlv-side, .mlv-top, .mlv-hero, .mlv-section-head, .mlv-foot, .mlv-code, .mlv-matrix th, .mlv-notice, .mlv-empty, .mlv-toast, .mlv-error {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        html.dark { color-scheme: dark; }
        html.dark .mlv {
            --bg: #0b0b0d; --panel: #131316; --text: #f4f4f5; --muted: #a1a1aa; --faint: #63636e;
            --line: #232328; --line-strong: #34343b; --hover: #1c1c21; --ink: #f4f4f5; --on-ink: #0b0b0d;
            --stage: #0f0f12; --dot: #26262c; --error-bg: #2a1214; --error: #fca5a5; --warn-bg: #241c0b; --warn-line: #4a3a12; --warn: #fcd34d;
            --shadow: none;
        }
        :where(.mlv-side, .mlv-top, .mlv-hero, .mlv-foot) :where(button) { font: inherit; color: inherit; }
        :where(.mlv-side, .mlv-top) :where(a) { color: inherit; }
        :where(.mlv-hero, .mlv-section-head, .mlv-notice, .mlv-empty, .mlv-toast) code, .mlv .mono { font: 12.5px/1.5 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .mlv svg.i { width: 16px; height: 16px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

        /* Sidebar */
        .mlv-side { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; gap: 14px; padding: 18px 14px; border-right: 1px solid var(--line); background: var(--panel); }
        .mlv-brand { display: flex; align-items: center; gap: 10px; padding: 2px 6px; text-decoration: none; }
        .mlv-logo { width: 22px; height: 22px; background: var(--ink); border-radius: 3px; transform: rotate(-6deg); }
        .mlv-brand b { font-size: 15px; letter-spacing: -.01em; }
        .mlv-brand span { color: var(--muted); font-size: 12px; margin-left: auto; }
        .mlv-search { position: relative; display: flex; align-items: center; }
        .mlv-search svg { position: absolute; left: 10px; color: var(--faint); }
        .mlv-search input { width: 100%; height: 36px; padding: 0 34px 0 34px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); color: var(--text); font: inherit; outline: none; transition: border-color .15s, box-shadow .15s; }
        .mlv-search input:focus { border-color: var(--line-strong); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 8%, transparent); }
        .mlv-search kbd { position: absolute; right: 8px; padding: 0 6px; border: 1px solid var(--line); border-radius: 6px; color: var(--faint); font: 11px/18px ui-monospace, monospace; background: var(--panel); }
        .mlv-label { padding: 0 8px; color: var(--faint); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .mlv-nav { list-style: none; margin: 0 -2px; padding: 0 2px; overflow-y: auto; flex: 1; }
        .mlv-nav a { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 9px; color: var(--muted); text-decoration: none; transition: background .12s, color .12s; }
        .mlv-nav a:hover { background: var(--hover); color: var(--text); }
        .mlv-nav a[aria-current] { background: var(--ink); color: var(--on-ink); }
        .mlv-nav a span:first-child { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mlv-nav small { font-size: 11px; opacity: .7; font-variant-numeric: tabular-nums; }
        .mlv-nav-empty { padding: 8px 10px; color: var(--faint); font-size: 13px; }

        /* Main */
        .mlv-main { min-width: 0; padding: 0 40px 80px; }
        .mlv-top { position: sticky; top: 0; z-index: 5; margin: 0 -40px; padding: 14px 40px; display: flex; align-items: center; gap: 12px; background: color-mix(in srgb, var(--bg) 85%, transparent); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .mlv-jump { display: flex; gap: 4px; overflow-x: auto; scrollbar-width: none; flex: 1; min-width: 0; }
        .mlv-jump a { padding: 5px 10px; border-radius: 8px; color: var(--muted); text-decoration: none; white-space: nowrap; font-size: 13px; }
        .mlv-jump a:hover { background: var(--hover); color: var(--text); }
        .mlv-jump a[aria-current] { background: var(--panel); color: var(--text); box-shadow: inset 0 0 0 1px var(--line); }
        .mlv-seg { display: inline-flex; padding: 3px; gap: 2px; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); flex: none; }
        .mlv-seg button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 26px; border: 0; border-radius: 7px; background: none; color: var(--muted); cursor: pointer; }
        .mlv-seg button:hover { color: var(--text); }
        .mlv-seg button[aria-pressed="true"] { background: var(--hover); color: var(--text); }

        .mlv-hero { padding: 36px 0 8px; }
        .mlv-hero h1 { margin: 0; font: 600 28px/1.2 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: -.02em; word-break: break-word; }
        .mlv-hero h1 span { color: var(--faint); }
        .mlv-file { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 0; border: 0; background: none; color: var(--muted); cursor: pointer; text-align: left; word-break: break-all; }
        .mlv-file:hover { color: var(--text); }
        .mlv-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 16px; }
        .mlv-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border: 1px solid var(--line); border-radius: 999px; background: var(--panel); color: var(--muted); font-size: 12.5px; }
        .mlv-chip b { color: var(--text); font-weight: 600; }

        .mlv-section { padding-top: 40px; scroll-margin-top: 64px; }
        .mlv-section-head { display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px; }
        .mlv-section-head h2 { margin: 0; font-size: 16px; font-weight: 600; letter-spacing: -.01em; }
        .mlv-section-head p { margin: 0; color: var(--muted); font-size: 13px; }

        .mlv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
        .mlv-card { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: var(--radius); background: var(--panel); box-shadow: var(--shadow); overflow: hidden; transition: border-color .15s; }
        .mlv-card:hover { border-color: var(--line-strong); }
        .mlv-stage { display: flex; align-items: center; justify-content: center; flex: 1; min-height: 132px; padding: 28px 20px; background-color: var(--stage); }
        html[data-mlv-stage="dots"] .mlv-stage, html[data-mlv-stage="dots"] .mlv-matrix { background-image: radial-gradient(var(--dot) 1px, transparent 1px); background-size: 14px 14px; background-position: 7px 7px; }
        .mlv-stage > * { max-width: 100%; }
        .mlv-foot { display: flex; align-items: center; gap: 8px; min-height: 42px; padding: 6px 6px 6px 14px; border-top: 1px solid var(--line); }
        .mlv-value { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--muted); }
        .mlv-value b { color: var(--text); font-weight: 600; }
        .mlv-actions { display: flex; gap: 2px; flex: none; }
        .mlv-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 0; border-radius: 8px; background: none; color: var(--muted); cursor: pointer; transition: background .12s, color .12s; }
        .mlv-icon:hover, .mlv-icon[aria-expanded="true"] { background: var(--hover); color: var(--text); }
        .mlv-icon.is-done { color: #16a34a; }
        .mlv-code { display: none; position: relative; border-top: 1px solid var(--line); background: var(--bg); }
        .mlv-card.is-open .mlv-code { display: block; }
        .mlv-code pre { margin: 0; padding: 12px 14px; max-height: 280px; overflow: auto; white-space: pre-wrap; word-break: break-word; color: var(--muted); }
        .mlv-error { display: flex; gap: 8px; align-items: flex-start; max-width: 100%; padding: 10px 12px; border-radius: 10px; background: var(--error-bg); color: var(--error); font-size: 12.5px; white-space: pre-wrap; word-break: break-word; }

        .mlv-matrix { overflow-x: auto; border: 1px solid var(--line); border-radius: var(--radius); background-color: var(--stage); box-shadow: var(--shadow); }
        .mlv-matrix table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .mlv-matrix th { padding: 12px 16px; color: var(--muted); font-weight: 500; text-align: center; white-space: nowrap; background: var(--panel); border-bottom: 1px solid var(--line); }
        .mlv-matrix th:first-child { position: sticky; left: 0; z-index: 1; text-align: left; border-right: 1px solid var(--line); }
        .mlv-matrix thead th:first-child { color: var(--faint); font-size: 12px; }
        .mlv-matrix tbody th { border-bottom: 0; }
        .mlv-matrix tbody tr + tr th { border-top: 1px solid var(--line); }
        .mlv-matrix td { padding: 22px 18px; text-align: center; vertical-align: middle; cursor: copy; border-radius: 10px; transition: box-shadow .12s; }
        .mlv-matrix td:hover { box-shadow: inset 0 0 0 1px var(--line-strong); }
        .mlv-matrix td > * { display: inline-flex; max-width: 100%; }

        .mlv-notice { display: flex; gap: 10px; align-items: flex-start; margin-top: 24px; padding: 12px 14px; border: 1px solid var(--warn-line); border-radius: 12px; background: var(--warn-bg); color: var(--warn); font-size: 13px; }
        .mlv-notice code { padding: 1px 5px; border-radius: 5px; background: color-mix(in srgb, currentColor 10%, transparent); }
        .mlv-empty { max-width: 520px; margin: 18vh auto 0; text-align: center; color: var(--muted); }
        .mlv-empty .mlv-logo { width: 44px; height: 44px; margin: 0 auto 20px; }
        .mlv-empty h1 { margin: 0 0 8px; color: var(--text); font-size: 20px; }
        .mlv-empty code { padding: 2px 6px; border-radius: 6px; background: var(--hover); color: var(--text); }

        .mlv-toast { position: fixed; left: 50%; bottom: 24px; z-index: 50; display: flex; align-items: center; gap: 8px; max-width: calc(100vw - 32px); padding: 9px 14px; border-radius: 10px; background: var(--ink); color: var(--on-ink); font-size: 13px; box-shadow: 0 8px 24px rgba(0, 0, 0, .18); opacity: 0; transform: translate(-50%, 8px); transition: opacity .16s, transform .16s; pointer-events: none; }
        .mlv-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .mlv-toast code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: .8; }

        @media (max-width: 860px) {
            .mlv { grid-template-columns: 1fr; }
            .mlv-side { position: static; height: auto; border-right: 0; border-bottom: 1px solid var(--line); gap: 10px; padding: 14px 16px; }
            .mlv-side .mlv-label { display: none; }
            .mlv-nav { display: flex; gap: 4px; overflow-x: auto; flex: none; margin: 0; padding: 0; scrollbar-width: none; }
            .mlv-nav a { white-space: nowrap; }
            .mlv-nav small { display: none; }
            .mlv-search kbd { display: none; }
            .mlv-main { padding: 0 16px 64px; }
            .mlv-top { margin: 0 -16px; padding: 10px 16px; }
            .mlv-hero { padding-top: 24px; }
            .mlv-hero h1 { font-size: 22px; }
            .mlv-grid { grid-template-columns: 1fr; }
            .mlv-stage { min-height: 104px; }
        }
        @media (prefers-reduced-motion: reduce) { .mlv * { transition: none !important; } }
    </style>
</head>
<body style="margin: 0">
@php
    $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        'code' => '<path d="m8 8-5 4 5 4"/><path d="m16 8 5 4-5 4"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'dots' => '<circle cx="6" cy="6" r="1"/><circle cx="12" cy="6" r="1"/><circle cx="18" cy="6" r="1"/><circle cx="6" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="18" cy="12" r="1"/><circle cx="6" cy="18" r="1"/><circle cx="12" cy="18" r="1"/><circle cx="18" cy="18" r="1"/>',
        'square' => '<rect x="4" y="4" width="16" height="16" rx="3"/>',
        'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
    ];
    $icon = fn (string $name) => '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<div class="mlv">
    <aside class="mlv-side">
        <a class="mlv-brand" href="{{ url($path) }}">
            <i class="mlv-logo"></i>
            <b>Malevich</b>
            <span>{{ count($components) }} {{ \Illuminate\Support\Str::plural('component', count($components)) }}</span>
        </a>

        <label class="mlv-search">
            {!! $icon('search') !!}
            <input type="search" placeholder="Search components" aria-label="Search components" data-mlv-search autocomplete="off">
            <kbd>/</kbd>
        </label>

        <div class="mlv-label">Components</div>
        <ul class="mlv-nav">
            @foreach ($components as $tag => $item)
                <li data-mlv-item="{{ $tag }}">
                    <a href="{{ url($path) }}?component={{ urlencode($tag) }}" @if ($tag === $name) aria-current="page" @endif>
                        <span>{{ $tag }}</span>
                        <small>{{ count($item['options']) }}</small>
                    </a>
                </li>
            @endforeach
            <li class="mlv-nav-empty" data-mlv-nothing hidden>Nothing found</li>
        </ul>
    </aside>

    <main class="mlv-main">
        <div class="mlv-top">
            <nav class="mlv-jump" aria-label="Sections">
                @if ($sheet)
                    <a href="#default">Default</a>
                    @foreach (array_keys($sheet['options']) as $option)
                        <a href="#option-{{ $option }}">{{ $option }}</a>
                    @endforeach
                    @if ($sheet['matrix'])
                        <a href="#matrix">{{ $sheet['matrix']['rows'] }} × {{ $sheet['matrix']['columns'] }}</a>
                    @endif
                @endif
            </nav>
            <div class="mlv-seg" role="group" aria-label="Preview background">
                <button type="button" data-mlv-stage="dots" title="Dotted background">{!! $icon('dots') !!}</button>
                <button type="button" data-mlv-stage="plain" title="Plain background">{!! $icon('square') !!}</button>
            </div>
            <div class="mlv-seg" role="group" aria-label="Theme">
                <button type="button" data-mlv-theme="light" title="Light">{!! $icon('sun') !!}</button>
                <button type="button" data-mlv-theme="dark" title="Dark (adds the dark class to &lt;html&gt;)">{!! $icon('moon') !!}</button>
            </div>
        </div>

        @if ($head === '')
            <div class="mlv-notice">
                {!! $icon('alert') !!}
                <div>
                    <b>Your CSS isn't loaded</b>, so the components look unstyled. Run <code>npm run dev</code> (or <code>npm run build</code>):
                    the catalog loads the same Vite entries as your app. CSS from elsewhere goes into <code>catalog.head</code> in <code>config/malevich.php</code>.
                </div>
            </div>
        @endif

        @if ($component === null)
            <div class="mlv-empty">
                <div class="mlv-logo"></div>
                <h1>No components yet</h1>
                <p>Every Blade component that uses <code>@@attributes</code> shows up here. Create one with <code>php artisan make:malevich button</code> and refresh.</p>
            </div>
        @else
            <header class="mlv-hero">
                <h1><span>&lt;x-</span>{{ $name }}<span>&gt;</span></h1>
                <button class="mlv-file mono" type="button" data-mlv-copy="{{ $relative($component['file']) }}" title="Copy the path">
                    {!! $icon('file') !!} {{ $relative($component['file']) }}
                </button>
                <div class="mlv-chips">
                    @forelse ($component['options'] as $option => $values)
                        <span class="mlv-chip"><b>{{ $option }}</b> {{ implode(' · ', $values) }}</span>
                    @empty
                        <span class="mlv-chip">No option values yet - fill in a class map like <b>@@variant(['primary' => '...'])</b> and every value shows up here.</span>
                    @endforelse
                </div>
            </header>

            <section class="mlv-section" id="default">
                <div class="mlv-section-head">
                    <h2>Default</h2>
                    <p>As <code>&lt;x-{{ $name }}&gt;</code> renders with no options set.</p>
                </div>
                <div class="mlv-grid">
                    @include('malevich::catalog-cell', ['cell' => $sheet['default'], 'icon' => $icon])
                </div>
            </section>

            @foreach ($sheet['options'] as $option => $cells)
                <section class="mlv-section" id="option-{{ $option }}">
                    <div class="mlv-section-head">
                        <h2 class="mono">{{ $option }}</h2>
                        <p>{{ count($cells) }} {{ \Illuminate\Support\Str::plural('value', count($cells)) }}</p>
                    </div>
                    <div class="mlv-grid">
                        @foreach ($cells as $cell)
                            @include('malevich::catalog-cell', ['cell' => $cell, 'icon' => $icon])
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if ($sheet['matrix'])
                <section class="mlv-section" id="matrix">
                    <div class="mlv-section-head">
                        <h2><span class="mono">{{ $sheet['matrix']['rows'] }}</span> × <span class="mono">{{ $sheet['matrix']['columns'] }}</span></h2>
                        <p>Every combination. Click one to copy its tag.</p>
                    </div>
                    <div class="mlv-matrix">
                        <table>
                            <thead>
                                <tr>
                                    <th class="mono">{{ $sheet['matrix']['rows'] }} \ {{ $sheet['matrix']['columns'] }}</th>
                                    @foreach (array_keys(\Illuminate\Support\Arr::first($sheet['matrix']['cells']) ?? []) as $column)
                                        <th class="mono">{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sheet['matrix']['cells'] as $row => $columns)
                                    <tr>
                                        <th class="mono" scope="row">{{ $row }}</th>
                                        @foreach ($columns as $cell)
                                            <td title="{{ $cell->tag }}" data-mlv-copy="{{ $cell->tag }}">
                                                @if ($cell->error !== null)
                                                    <span class="mlv-error">{{ $cell->error }}</span>
                                                @else
                                                    {!! $cell->html !!}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endif
    </main>
</div>
<div class="mlv-toast" role="status" aria-live="polite" data-mlv-toast></div>

<script>
    (() => {
        const root = document.documentElement;
        const store = (key, value) => { try { localStorage.setItem(key, value); } catch (e) {} };

        // Toast
        const toast = document.querySelector('[data-mlv-toast]');
        let timer;
        const say = (title, detail = '') => {
            toast.replaceChildren(document.createTextNode(title));
            if (detail) {
                const code = document.createElement('code');
                code.textContent = detail;
                toast.append(code);
            }
            toast.classList.add('is-shown');
            clearTimeout(timer);
            timer = setTimeout(() => toast.classList.remove('is-shown'), 1600);
        };
        const copy = async (text, button) => {
            try {
                await navigator.clipboard.writeText(text);
                say('Copied', text);
                if (button?.classList.contains('mlv-icon')) {
                    const html = button.innerHTML;
                    button.classList.add('is-done');
                    button.innerHTML = @json($icon('check'));
                    setTimeout(() => { button.classList.remove('is-done'); button.innerHTML = html; }, 1200);
                }
            } catch (e) {
                say('Copy failed');
            }
        };

        // Theme and preview background
        const sync = () => {
            const dark = root.classList.contains('dark');
            document.querySelectorAll('[data-mlv-theme]').forEach((b) => b.setAttribute('aria-pressed', String((b.dataset.mlvTheme === 'dark') === dark)));
            document.querySelectorAll('[data-mlv-stage]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.mlvStage === (root.dataset.mlvStage || 'dots'))));
        };
        sync();

        document.addEventListener('click', (event) => {
            const target = event.target.closest('[data-mlv-copy], [data-mlv-html], [data-mlv-theme], [data-mlv-stage]');

            if (target?.matches('button[data-mlv-theme]')) {
                root.classList.toggle('dark', target.dataset.mlvTheme === 'dark');
                store('malevich.dark', root.classList.contains('dark') ? '1' : '0');
                sync();
            } else if (target?.matches('button[data-mlv-stage]')) {
                root.dataset.mlvStage = target.dataset.mlvStage;
                store('malevich.stage', target.dataset.mlvStage);
                sync();
            } else if (target?.matches('[data-mlv-html]')) {
                const open = target.closest('.mlv-card').classList.toggle('is-open');
                target.setAttribute('aria-expanded', String(open));
            } else if (target?.matches('[data-mlv-copy]')) {
                event.preventDefault();
                copy(target.dataset.mlvCopy, target);
            } else if (event.target.closest('.mlv-stage') && event.target.closest('a[href], [type=submit]')) {
                // Previews are live, but a link or a submit button must not leave the page.
                event.preventDefault();
            }
        });

        // Search: "/" focuses it, Enter opens the first match
        const search = document.querySelector('[data-mlv-search]');
        const items = [...document.querySelectorAll('[data-mlv-item]')];
        const nothing = document.querySelector('[data-mlv-nothing]');
        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();
            let shown = 0;
            items.forEach((item) => {
                item.hidden = query !== '' && ! item.dataset.mlvItem.toLowerCase().includes(query);
                shown += item.hidden ? 0 : 1;
            });
            nothing.hidden = shown > 0;
        });
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') items.find((item) => ! item.hidden)?.querySelector('a')?.click();
            if (event.key === 'Escape') { search.value = ''; search.dispatchEvent(new Event('input')); search.blur(); }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === '/' && ! event.target.closest('input, textarea, select, [contenteditable]')) {
                event.preventDefault();
                search.focus();
            }
        });

        document.querySelector('.mlv-nav [aria-current]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });

        // The section in view is highlighted in the top bar.
        const links = new Map([...document.querySelectorAll('.mlv-jump a')].map((a) => [a.hash.slice(1), a]));
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                links.forEach((a) => a.removeAttribute('aria-current'));
                links.get(entry.target.id)?.setAttribute('aria-current', 'true');
            });
        }, { rootMargin: '-64px 0px -60% 0px' });
        links.forEach((a, id) => { const section = document.getElementById(id); if (section) observer.observe(section); });
    })();
</script>
</body>
</html>
