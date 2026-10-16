@php
    /*
     * Tag names are always written as &lt;x-... in this file: Blade compiles a literal
     * opening x- tag as a component, even inside a PHP string.
     */
    $relative = function (string $file): string {
        foreach ([base_path(), getcwd() ?: ''] as $root) {
            if ($root !== '' && str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
                return str_replace('\\', '/', substr($file, strlen($root) + 1));
            }
        }

        return str_replace('\\', '/', $file);
    };

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
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'reset' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
        'small' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'large' => '<rect x="4" y="4" width="16" height="16" rx="2.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'paint' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13 7 4 4"/>',
        'medium' => '<rect x="4" y="5" width="7" height="14" rx="1.5"/><rect x="13" y="5" width="7" height="14" rx="1.5"/>',
    ];
    $icon = fn (string $name, string $class = '') => '<svg class="i '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ rescue(fn () => csrf_token(), '', false) }}">
    <title>@if ($name)&lt;x-{{ $name }}&gt; · @endif Malevich catalog</title>
    <script>
        try {
            const root = document.documentElement;
            root.dataset.mlvUi = localStorage.getItem('malevich.theme') || 'light';
            root.dataset.mlvStage = localStorage.getItem('malevich.stage') || 'dots';
            root.dataset.mlvSize = localStorage.getItem('malevich.size') || 'l';
        } catch (e) {}
    </script>
    {!! $head !!}
    <style>
        .mlv, .mlv *, .mlv *::before, .mlv *::after, .mlv-dialog, .mlv-dialog * { box-sizing: border-box; }
        .mlv, .mlv-dialog, .mlv-toast {
            --bg: #f6f6f7; --panel: #ffffff; --text: #0a0a0b; --muted: #6b6b76; --faint: #a1a1aa;
            --line: #e7e7ea; --line-strong: #d4d4d8; --hover: #f1f1f3; --ink: #0a0a0b; --on-ink: #ffffff;
            --stage: #ffffff; --dot: #e4e4e7; --stage-dark: #0d0d10; --dot-dark: #23232a;
            --error-bg: #fef2f2; --error: #b91c1c; --warn-bg: #fffbeb; --warn-line: #fde68a; --warn: #92400e;
            --code-bg: #fafafa; --shadow: 0 1px 2px rgba(10, 10, 11, .04), 0 1px 1px rgba(10, 10, 11, .03);
            --radius: 16px; --card: 300px; --stage-h: 200px;
            font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased;
        }
        html[data-mlv-ui="dark"] { color-scheme: dark; }
        html[data-mlv-ui="dark"] .mlv, html[data-mlv-ui="dark"] .mlv-dialog, html[data-mlv-ui="dark"] .mlv-toast {
            --bg: #0b0b0d; --panel: #131316; --text: #f4f4f5; --muted: #a1a1aa; --faint: #63636e;
            --line: #232328; --line-strong: #34343b; --hover: #1c1c21; --ink: #f4f4f5; --on-ink: #0b0b0d;
            --stage: #ffffff; --dot: #e4e4e7; --error-bg: #2a1214; --error: #fca5a5; --warn-bg: #241c0b; --warn-line: #4a3a12; --warn: #fcd34d;
            --code-bg: #0f0f12; --shadow: none;
        }
        html[data-mlv-size="s"] .mlv { --card: 220px; --stage-h: 140px; }
        html[data-mlv-size="l"] .mlv { --card: 440px; --stage-h: 280px; }
        .mlv { display: grid; grid-template-columns: 264px minmax(0, 1fr); min-height: 100vh; background: var(--bg); color: var(--text); }

        /* The catalog's own UI. Previews are left alone: they keep the app's font and styles. */
        .mlv-side, .mlv-top, .mlv-hero, .mlv-section-head, .mlv-foot, .mlv-controls, .mlv-play-tag, .mlv-matrix th,
        .mlv-notice, .mlv-empty, .mlv-toast, .mlv-error, .mlv-dialog {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        :where(.mlv-side, .mlv-top, .mlv-hero, .mlv-section-head, .mlv-foot, .mlv-controls, .mlv-dialog, .mlv-play-bar) :where(button, select, input, textarea) { font: inherit; color: inherit; }
        :where(.mlv-side, .mlv-top) :where(a) { color: inherit; }
        .mlv .mono, .mlv-dialog .mono, :where(.mlv-hero, .mlv-section-head, .mlv-notice, .mlv-empty, .mlv-toast, .mlv-controls) code {
            font: 12.5px/1.6 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }
        .mlv svg.i, .mlv-dialog svg.i { width: 16px; height: 16px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

        /* Sidebar */
        .mlv-side { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; gap: 14px; padding: 18px 14px; border-right: 1px solid var(--line); background: var(--panel); }
        .mlv-brand { display: flex; align-items: center; gap: 10px; padding: 2px 6px; text-decoration: none; }
        .mlv-logo { width: 22px; height: 22px; background: var(--ink); border-radius: 3px; transform: rotate(-6deg); }
        .mlv-brand b { font-size: 15px; letter-spacing: -.01em; }
        .mlv-brand span { color: var(--muted); font-size: 12px; margin-left: auto; }
        .mlv-search { position: relative; display: flex; align-items: center; }
        .mlv-search svg { position: absolute; left: 10px; color: var(--faint); }
        .mlv-search input { width: 100%; height: 36px; padding: 0 34px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); color: var(--text); outline: none; transition: border-color .15s, box-shadow .15s; }
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

        /* Top bar */
        .mlv-main { min-width: 0; padding: 0 40px 96px; }
        .mlv-top { position: sticky; top: 0; z-index: 5; margin: 0 -40px; padding: 12px 40px; display: flex; align-items: center; gap: 10px; background: color-mix(in srgb, var(--bg) 85%, transparent); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .mlv-jump { display: flex; gap: 4px; overflow-x: auto; scrollbar-width: none; flex: 1; min-width: 0; }
        .mlv-jump a { padding: 5px 10px; border-radius: 8px; color: var(--muted); text-decoration: none; white-space: nowrap; font-size: 13px; }
        .mlv-jump a:hover { background: var(--hover); color: var(--text); }
        .mlv-jump a[aria-current] { background: var(--panel); color: var(--text); box-shadow: inset 0 0 0 1px var(--line); }
        .mlv-seg { display: inline-flex; padding: 3px; gap: 2px; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); flex: none; }
        .mlv-seg button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 26px; border: 0; border-radius: 7px; background: none; color: var(--muted); cursor: pointer; }
        .mlv-seg button:hover { color: var(--text); }
        .mlv-seg button[aria-pressed="true"] { background: var(--hover); color: var(--text); }

        /* Header */
        .mlv-hero { padding: 36px 0 4px; }
        .mlv-hero h1 { margin: 0; font: 600 28px/1.2 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: -.02em; word-break: break-word; }
        .mlv-hero h1 span { color: var(--faint); }
        .mlv-file { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 0; border: 0; background: none; color: var(--muted); cursor: pointer; text-align: left; word-break: break-all; }
        .mlv-file:hover { color: var(--text); }
        .mlv-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 16px; }
        .mlv-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border: 1px solid var(--line); border-radius: 999px; background: var(--panel); color: var(--muted); font-size: 12.5px; }
        .mlv-chip b { color: var(--text); font-weight: 600; }

        .mlv-section { padding-top: 44px; scroll-margin-top: 64px; }
        .mlv-section-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .mlv-section-head h2 { margin: 0; font-size: 16px; font-weight: 600; letter-spacing: -.01em; }
        .mlv-section-head p { margin: 0; color: var(--muted); font-size: 13px; flex: 1; }

        /* Preview canvas: light by default, dark per card */
        .mlv-stage { position: relative; display: flex; align-items: center; justify-content: center; flex: 1; min-height: var(--stage-h); padding: 32px 24px; background-color: var(--stage); color: #0a0a0b; transition: background-color .2s; }
        .mlv-stage.dark { background-color: var(--stage-dark); color: #f4f4f5; color-scheme: dark; }
        html[data-mlv-stage="dots"] .mlv-stage { background-image: radial-gradient(var(--dot) 1px, transparent 1px); background-size: 16px 16px; background-position: 8px 8px; }
        html[data-mlv-stage="dots"] .mlv-stage.dark { background-image: radial-gradient(var(--dot-dark) 1px, transparent 1px); }
        .mlv-stage > * { max-width: 100%; }

        /* Cards */
        .mlv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(var(--card), 100%), 1fr)); gap: 16px; }
        .mlv-card { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: var(--radius); background: var(--panel); box-shadow: var(--shadow); overflow: hidden; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .mlv-card:hover { border-color: var(--line-strong); box-shadow: 0 6px 24px -12px rgba(10, 10, 11, .18); }
        .mlv-foot { display: flex; align-items: center; gap: 8px; min-height: 44px; padding: 6px 6px 6px 14px; border-top: 1px solid var(--line); }
        .mlv-value { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--muted); }
        .mlv-value b { color: var(--text); font-weight: 600; }
        .mlv-actions { display: flex; gap: 2px; flex: none; }
        .mlv-icon { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 0; border-radius: 8px; background: none; color: var(--muted); cursor: pointer; transition: background .12s, color .12s; }
        .mlv-icon:hover { background: var(--hover); color: var(--text); }
        .mlv-icon .i-done { display: none; }
        .mlv-icon.is-done { color: #16a34a; }
        .mlv-icon.is-done .i-copy { display: none; }
        .mlv-icon.is-done .i-done { display: block; }
        [data-mlv-theme-target] .i-sun, [data-mlv-theme-target].is-dark .i-moon { display: none; }
        [data-mlv-theme-target].is-dark .i-sun { display: block; }
        .mlv-error { display: flex; gap: 8px; align-items: flex-start; max-width: 100%; padding: 10px 12px; border-radius: 10px; background: var(--error-bg); color: var(--error); font-size: 12.5px; white-space: pre-wrap; word-break: break-word; text-align: left; }

        /* Playground */
        .mlv-play { --controls: 340px; display: grid; grid-template-columns: minmax(0, 1fr) 9px var(--controls); border: 1px solid var(--line); border-radius: var(--radius); background: var(--panel); box-shadow: var(--shadow); overflow: hidden; }
        .mlv-play .mlv-stage { min-height: calc(var(--stage-h) + 140px); padding: 48px 32px; }
        .mlv-play-main { display: flex; flex-direction: column; min-width: 0; }
        .mlv-resize { position: relative; width: 9px; margin: 0; padding: 0; border: 0; background: none; cursor: col-resize; touch-action: none; }
        .mlv-resize::after { content: ""; position: absolute; top: 0; bottom: 0; left: 4px; width: 1px; background: var(--line); transition: background .12s, box-shadow .12s; }
        .mlv-resize:hover::after, .mlv-resize:focus-visible::after, .mlv-play.is-resizing .mlv-resize::after { background: var(--ink); box-shadow: 0 0 0 1px var(--ink); }
        .mlv-resize:focus-visible { outline: none; }
        .mlv-play.is-resizing { user-select: none; cursor: col-resize; }
        .mlv-play-bar { display: flex; align-items: center; gap: 8px; padding: 6px 6px 6px 14px; border-top: 1px solid var(--line); background: var(--panel); }
        .mlv-play-tag { flex: 1; min-width: 0; max-height: 168px; margin: 0; padding: 4px 0; overflow: auto; white-space: pre; color: var(--text); scrollbar-width: thin; }        .mlv-controls { display: flex; flex-direction: column; min-width: 0; }
        .mlv-controls-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 10px 12px 18px; }
        .mlv-controls-head h3 { margin: 0; font-size: 13px; font-weight: 600; }
        .mlv-group { border-top: 1px solid var(--line); }
        .mlv-group > summary { display: flex; align-items: center; gap: 8px; padding: 12px 18px; list-style: none; cursor: pointer; user-select: none; font-size: 12px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
        .mlv-group > summary::-webkit-details-marker { display: none; }
        .mlv-group > summary::before { content: ""; width: 6px; height: 6px; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; transform: rotate(-45deg); transition: transform .15s; margin-right: 2px; }
        .mlv-group[open] > summary::before { transform: rotate(45deg); }
        .mlv-group > summary:hover { color: var(--text); }
        .mlv-group > summary small { margin-left: auto; padding: 0 7px; border-radius: 999px; background: var(--hover); color: var(--muted); font-size: 11px; font-weight: 500; letter-spacing: 0; text-transform: none; }
        .mlv-group-body { display: flex; flex-direction: column; gap: 12px; padding: 0 18px 18px; }
        .mlv-help { display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; border: 1px solid var(--line-strong); border-radius: 50%; color: var(--faint); font-size: 10px; font-weight: 600; letter-spacing: 0; cursor: help; text-transform: none; }
        .mlv-option { display: flex; flex-direction: column; gap: 6px; }
        .mlv-option-name { color: var(--text); font-size: 12.5px !important; font-weight: 500; }
        .mlv-pills { display: flex; flex-wrap: wrap; gap: 4px; }
        .mlv-pills button { height: 28px; padding: 0 11px; border: 1px solid var(--line); border-radius: 8px; background: var(--panel); color: var(--muted); font-size: 12.5px; cursor: pointer; transition: all .12s; }
        .mlv-pills button:hover { color: var(--text); border-color: var(--line-strong); }
        .mlv-pills button[aria-pressed="true"] { background: var(--ink); border-color: var(--ink); color: var(--on-ink); }
        .mlv-pills button[data-mlv-value=""] { font-style: italic; }
        .mlv-segmented { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; padding: 3px; gap: 2px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); }
        .mlv-segmented button { height: 28px; border: 0; border-radius: 7px; background: none; color: var(--muted); font-size: 12.5px; font-weight: 500; cursor: pointer; }
        .mlv-segmented button[aria-selected="true"] { background: var(--panel); color: var(--text); box-shadow: var(--shadow), 0 0 0 1px var(--line); }
        .mlv-input { width: 100%; min-width: 0; height: 34px; padding: 0 10px; border: 1px solid var(--line); border-radius: 9px; background: var(--bg); color: var(--text); outline: none; transition: border-color .15s, box-shadow .15s, background .15s; }
        textarea.mlv-input { height: auto; min-height: 64px; padding: 8px 10px; resize: vertical; }
        .mlv-input:hover { border-color: var(--line-strong); }
        .mlv-input:focus { border-color: var(--line-strong); background: var(--panel); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 8%, transparent); }
        .mlv-input::placeholder { color: var(--faint); }
        .mlv-slot { display: flex; flex-direction: column; gap: 6px; }
        .mlv-slot-head { display: flex; align-items: center; gap: 4px; min-height: 24px; }
        .mlv-slot-head b { flex: 1; min-width: 0; font-size: 12.5px; font-weight: 500; }
        .mlv-slot-head b span { color: var(--faint); font-weight: 400; }
        .mlv-mini { display: inline-flex; align-items: center; gap: 5px; height: 24px; padding: 0 8px; border: 0; border-radius: 6px; background: none; color: var(--faint); font: 12px/24px "JetBrains Mono", ui-monospace, monospace; cursor: pointer; }
        .mlv-mini:hover, .mlv-mini[aria-pressed="true"] { background: var(--hover); color: var(--text); }
        .mlv-mini svg.i { width: 14px; height: 14px; }
        .mlv-mini.is-icon { display: inline-flex; align-items: center; padding: 0 5px; }
        .mlv-attrs { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .mlv-attrs:empty { display: none; }
        .mlv-attr { display: grid; grid-template-columns: minmax(56px, 34%) minmax(0, 1fr) 28px; align-items: center; background: var(--bg); }
        .mlv-attr + .mlv-attr { border-top: 1px solid var(--line); }
        .mlv-attr > b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 0 10px; color: var(--muted); font: 500 12px/34px "JetBrains Mono", ui-monospace, monospace; border-right: 1px solid var(--line); }
        .mlv-attr .mlv-input { height: 34px; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
        .mlv-attr .mlv-input:focus { background: var(--panel); }
        .mlv-attr .mlv-mini { justify-self: center; }
        .mlv-adder { position: relative; display: flex; align-items: center; }
        .mlv-adder > svg { position: absolute; left: 10px; color: var(--faint); pointer-events: none; }
        .mlv-adder .mlv-input { padding-left: 32px; padding-right: 58px; border-style: dashed; background: transparent; }
        .mlv-adder .mlv-input:focus { border-style: solid; }
        .mlv-adder button { position: absolute; right: 4px; height: 26px; padding: 0 10px; border: 0; border-radius: 6px; background: var(--ink); color: var(--on-ink); font-size: 12px; font-weight: 500; cursor: pointer; opacity: 0; pointer-events: none; transition: opacity .12s; }
        .mlv-adder:focus-within button, .mlv-adder.has-value button { opacity: 1; pointer-events: auto; }
        .mlv-snippets { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
        .mlv-snippets > span { color: var(--faint); font-size: 12px; margin-right: 2px; }
        .mlv-snippets button { height: 24px; padding: 0 8px; border: 1px dashed var(--line-strong); border-radius: 7px; background: none; color: var(--muted); font: 12px/1 "JetBrains Mono", ui-monospace, monospace; cursor: pointer; }
        .mlv-snippets button::before { content: "+ "; color: var(--faint); }
        .mlv-snippets button:hover { color: var(--text); border-style: solid; }
        .mlv-snippets button:disabled { display: none; }
        .mlv-lock { margin: 0; padding: 10px 12px; border-radius: 10px; background: var(--hover); color: var(--muted); font-size: 12.5px; }

        /* Blade editor: a transparent textarea over the highlighted code */
        .mlv-editor { position: relative; border: 1px solid var(--line); border-radius: 10px; background: var(--code-bg); transition: border-color .15s, box-shadow .15s; }
        .mlv-editor:focus-within { border-color: var(--line-strong); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 8%, transparent); }
        .mlv-editor pre, .mlv-editor textarea { margin: 0; padding: 10px 12px; border: 0; font: 12.5px/1.6 "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace !important; white-space: pre-wrap; word-break: break-word; tab-size: 4; letter-spacing: 0; }
        .mlv-editor pre { position: absolute; inset: 0; overflow: hidden; color: var(--text); pointer-events: none; }
        .mlv-editor textarea { position: relative; display: block; width: 100%; min-height: 200px; resize: vertical; background: transparent; color: transparent; caret-color: var(--text); outline: none; }
        .mlv-editor textarea::selection { background: color-mix(in srgb, #3b82f6 30%, transparent); color: transparent; }
        .mlv-play.is-loading .mlv-stage > * { opacity: .55; transition: opacity .15s; }

        /* Matrix */
        .mlv-matrix { overflow-x: auto; border: 1px solid var(--line); border-radius: var(--radius); background: var(--panel); box-shadow: var(--shadow); }
        .mlv-matrix table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .mlv-matrix th { padding: 12px 16px; color: var(--muted); font-weight: 500; text-align: center; white-space: nowrap; background: var(--panel); border-bottom: 1px solid var(--line); }
        .mlv-matrix th:first-child { position: sticky; left: 0; z-index: 1; text-align: left; border-right: 1px solid var(--line); }
        .mlv-matrix thead th:first-child { color: var(--faint); font-size: 12px; }
        .mlv-matrix tbody th { border-bottom: 0; }
        .mlv-matrix tbody tr + tr th { border-top: 1px solid var(--line); }
        .mlv-matrix td.mlv-stage { display: table-cell; min-height: 0; padding: 26px 20px; text-align: center; vertical-align: middle; cursor: copy; }
        .mlv-matrix td.mlv-stage:hover { box-shadow: inset 0 0 0 1px var(--line-strong); }
        .mlv-matrix td.mlv-stage > * { display: inline-flex; }

        /* Notices */
        .mlv-notice { display: flex; gap: 10px; align-items: flex-start; margin-top: 24px; padding: 12px 14px; border: 1px solid var(--warn-line); border-radius: 12px; background: var(--warn-bg); color: var(--warn); font-size: 13px; }
        .mlv-notice code { padding: 1px 5px; border-radius: 5px; background: color-mix(in srgb, currentColor 10%, transparent); }
        .mlv-empty { max-width: 520px; margin: 18vh auto 0; text-align: center; color: var(--muted); }
        .mlv-empty .mlv-logo { width: 44px; height: 44px; margin: 0 auto 20px; }
        .mlv-empty h1 { margin: 0 0 8px; color: var(--text); font-size: 20px; }
        .mlv-empty code { padding: 2px 6px; border-radius: 6px; background: var(--hover); color: var(--text); }

        /* Code dialog */
        .mlv-dialog { position: fixed; inset: 0; margin: auto; height: fit-content; width: min(820px, calc(100vw - 32px)); max-height: min(720px, calc(100vh - 64px)); padding: 0; border: 1px solid var(--line); border-radius: 18px; background: var(--panel); color: var(--text); box-shadow: 0 24px 80px -20px rgba(0, 0, 0, .35); overflow: hidden; }
        .mlv-dialog[open] { display: flex; flex-direction: column; animation: mlv-in .16s ease-out; }
        .mlv-dialog::backdrop { background: rgba(10, 10, 11, .5); }
        @keyframes mlv-in { from { opacity: 0; transform: translateY(8px) scale(.98); } }
        .mlv-dialog header { display: flex; align-items: center; gap: 12px; padding: 14px 14px 14px 20px; border-bottom: 1px solid var(--line); }
        .mlv-dialog header h2 { margin: 0; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 14px; font-weight: 600; }
        .mlv-tabs { display: flex; gap: 2px; padding: 3px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); }
        .mlv-tabs button { padding: 4px 12px; border: 0; border-radius: 7px; background: none; color: var(--muted); font-size: 13px; cursor: pointer; }
        .mlv-tabs button[aria-selected="true"] { background: var(--panel); color: var(--text); box-shadow: var(--shadow); }
        .mlv-dialog .mlv-code { position: relative; flex: 1; min-height: 0; overflow: auto; background: var(--code-bg); }
        .mlv-dialog pre { margin: 0; padding: 20px 22px; white-space: pre-wrap; word-break: break-word; color: var(--text); }
        .tok-tag { color: #2563eb; }
        .tok-attr { color: #9333ea; }
        .tok-str { color: #16a34a; }
        .tok-comment { color: var(--faint); }
        html[data-mlv-ui="dark"] .tok-tag { color: #60a5fa; }
        html[data-mlv-ui="dark"] .tok-attr { color: #c084fc; }
        html[data-mlv-ui="dark"] .tok-str { color: #4ade80; }
        .tok-echo { color: #d97706; }
        html[data-mlv-ui="dark"] .tok-echo { color: #fbbf24; }
        .tok-punct { color: var(--faint); }
        .mlv-dialog footer { display: flex; align-items: center; gap: 8px; padding: 12px 14px 12px 20px; border-top: 1px solid var(--line); color: var(--muted); font-size: 12.5px; }
        .mlv-dialog footer span { flex: 1; }
        .mlv-button { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--panel); color: var(--text); font-size: 13px; cursor: pointer; }
        .mlv-button:hover { border-color: var(--line-strong); }
        .mlv-button.is-done { color: #16a34a; }

        .mlv-toast { position: fixed; left: 50%; bottom: 24px; z-index: 60; display: flex; align-items: center; gap: 8px; max-width: calc(100vw - 32px); padding: 9px 14px; border-radius: 10px; border: 1px solid var(--line-strong); background: var(--ink); color: var(--on-ink); font-size: 13px; font-weight: 500; box-shadow: 0 12px 32px -8px rgba(0, 0, 0, .35); opacity: 0; transform: translate(-50%, 8px); transition: opacity .16s, transform .16s; pointer-events: none; }
        .mlv-toast.is-shown { opacity: 1; transform: translate(-50%, 0); }
        .mlv-toast code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: .8; font: 12px ui-monospace, monospace; }

        @media (max-width: 1080px) {
            .mlv-play { grid-template-columns: 1fr; }
            .mlv-play-main { border-bottom: 1px solid var(--line); }
            .mlv-resize { display: none; }
        }
        @media (max-width: 860px) {
            .mlv { grid-template-columns: 1fr; }
            .mlv-side { position: static; height: auto; border-right: 0; border-bottom: 1px solid var(--line); gap: 10px; padding: 14px 16px; }
            .mlv-side .mlv-label, .mlv-search kbd, .mlv-nav small, [data-mlv-size-group] { display: none; }
            .mlv-nav { display: flex; gap: 4px; overflow-x: auto; flex: none; margin: 0; padding: 0; scrollbar-width: none; }
            .mlv-nav a { white-space: nowrap; }
            .mlv-main { padding: 0 16px 64px; }
            .mlv-top { margin: 0 -16px; padding: 10px 16px; }
            .mlv-hero { padding-top: 24px; }
            .mlv-hero h1 { font-size: 22px; }
            .mlv { --card: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { .mlv *, .mlv-dialog { transition: none !important; animation: none !important; } }
    </style>
</head>
<body style="margin: 0">
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
                    <a href="#playground">Playground</a>
                    @foreach (array_keys($sheet['options']) as $option)
                        <a href="#option-{{ $option }}">{{ $option }}</a>
                    @endforeach
                    @if ($sheet['matrix'])
                        <a href="#matrix">{{ $sheet['matrix']['rows'] }} × {{ $sheet['matrix']['columns'] }}</a>
                    @endif
                @endif
            </nav>
            <div class="mlv-seg" role="group" aria-label="Preview size" data-mlv-size-group>
                <button type="button" data-mlv-size="s" title="Small previews">{!! $icon('small') !!}</button>
                <button type="button" data-mlv-size="m" title="Medium previews">{!! $icon('medium') !!}</button>
                <button type="button" data-mlv-size="l" title="Large previews">{!! $icon('large') !!}</button>
            </div>
            <div class="mlv-seg" role="group" aria-label="Preview background">
                <button type="button" data-mlv-stage="dots" title="Dotted background">{!! $icon('dots') !!}</button>
                <button type="button" data-mlv-stage="plain" title="Plain background">{!! $icon('square') !!}</button>
            </div>
            <div class="mlv-seg" role="group" aria-label="Theme of the page and every preview">
                <button type="button" data-mlv-theme="light" title="Light: the page and every preview">{!! $icon('sun') !!}</button>
                <button type="button" data-mlv-theme="dark" title="Dark: the page and every preview">{!! $icon('moon') !!}</button>
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

            {{-- Playground: pick values, type the slot, see it live --}}
            <section class="mlv-section" id="playground">
                <div class="mlv-section-head">
                    <h2>Playground</h2>
                    <p>Pick values and type the content - the preview updates as you go.</p>
                </div>
                <div class="mlv-play" data-mlv-play data-mlv-theme-target
                     data-mlv-url="{{ url($path.'/preview') }}" data-mlv-component="{{ $name }}" data-mlv-blade-allowed="{{ $bladeAllowed ? '1' : '0' }}">
                    <div class="mlv-play-main">
                        <div class="mlv-stage" data-mlv-canvas data-mlv-play-stage>
                            @if ($sheet['default']->error !== null)
                                <span class="mlv-error">{!! $icon('alert') !!}<span>{{ $sheet['default']->error }}</span></span>
                            @else
                                {!! $sheet['default']->html !!}
                            @endif
                        </div>
                        <div class="mlv-play-bar">
                            <pre class="mlv-play-tag mono" data-mlv-play-tag>{{ $sheet['default']->tag }}</pre>
                            <span class="mlv-actions">
                                <button class="mlv-icon" type="button" data-mlv-card-theme title="Dark / light for this preview">{!! $icon('moon', 'i-moon') !!}{!! $icon('sun', 'i-sun') !!}</button>
                                <button class="mlv-icon" type="button" data-mlv-code title="View the code">{!! $icon('code') !!}</button>
                                <button class="mlv-icon" type="button" data-mlv-copy="{{ $sheet['default']->tag }}" data-mlv-play-copy title="Copy the tag">{!! $icon('copy', 'i-copy') !!}{!! $icon('check', 'i-done') !!}</button>
                            </span>
                        </div>
                        <textarea hidden data-mlv-source>{{ $sheet['default']->html }}</textarea>
                    </div>

                    <button class="mlv-resize" type="button" data-mlv-resize aria-label="Resize the controls (drag, or arrow keys)" title="Drag to resize"></button>

                    <form class="mlv-controls" data-mlv-controls onsubmit="return false">
                        <div class="mlv-controls-head">
                            <h3>Controls</h3>
                            <button class="mlv-mini" type="button" data-mlv-reset title="Back to the defaults">{!! $icon('reset') !!} reset</button>
                        </div>

                        @if ($component['options'] !== [])
                            <details class="mlv-group" open>
                                <summary>Options <small>{{ count($component['options']) }}</small></summary>
                                <div class="mlv-group-body">
                                    @foreach ($component['options'] as $option => $values)
                                        <div class="mlv-option">
                                            <span class="mlv-option-name mono">{{ $option }}</span>
                                            <div class="mlv-pills" role="group" aria-label="{{ $option }}" data-mlv-option="{{ $option }}">
                                                <button type="button" data-mlv-value="" aria-pressed="true" title="Not set: the @@props default applies">default</button>
                                                @foreach ($values as $value)
                                                    <button type="button" data-mlv-value="{{ $value }}" aria-pressed="false">{{ $value }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        <details class="mlv-group" open>
                            <summary>
                                Content
                                <span class="mlv-help" title="Slots: plain text per slot, shown exactly as typed. Blade: any code - HTML, slots, other components (local only).">?</span>
                            </summary>
                            <div class="mlv-group-body">
                                <div class="mlv-segmented" role="tablist">
                                    <button type="button" role="tab" data-mlv-mode="slots" aria-selected="true">Slots</button>
                                    <button type="button" role="tab" data-mlv-mode="blade" aria-selected="false">Blade</button>
                                </div>

                                <div class="mlv-group-body" style="padding: 0" data-mlv-pane="slots">
                                    <div class="mlv-group-body" style="padding: 0" data-mlv-slot-list></div>
                                    <label class="mlv-adder">
                                        {!! $icon('plus') !!}
                                        <input class="mlv-input mono" data-mlv-new-slot placeholder="Add a slot" aria-label="Name of a new slot" autocomplete="off" spellcheck="false">
                                        <button type="button" data-mlv-add-slot>Add</button>
                                    </label>
                                </div>

                                <div class="mlv-group-body" style="padding: 0" data-mlv-pane="blade" hidden>
                                    @if ($bladeAllowed)
                                        <div class="mlv-editor">
                                            <pre aria-hidden="true" data-mlv-highlight></pre>
                                            <textarea data-mlv-blade spellcheck="false" autocapitalize="off" aria-label="Content as Blade">{{ $sheet['slot'] }}</textarea>
                                        </div>
                                        <div class="mlv-snippets">
                                            <span>Insert</span>
                                            @foreach (array_keys($snippets) as $snippet)
                                                <button type="button" data-mlv-snippet="{{ $snippet }}" title="Insert with its usage example">{{ $snippet }}</button>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mlv-lock">Blade content runs code, so it works only in the <b>local</b> environment. Use the slots here.</p>
                                    @endif
                                </div>
                            </div>
                        </details>

                        <details class="mlv-group" open>
                            <summary>
                                Attributes
                                <span class="mlv-help" title="Anything on the tag: class, href, id, wire:click, props. true makes a flag (disabled), false turns it off. Values are plain text.">?</span>
                                <small data-mlv-attr-count>0</small>
                            </summary>
                            <div class="mlv-group-body">
                                <div class="mlv-attrs" data-mlv-attr-list></div>
                                <label class="mlv-adder">
                                    {!! $icon('plus') !!}
                                    <input class="mlv-input mono" data-mlv-new-attr list="mlv-attribute-names" placeholder="Add an attribute" aria-label="Name of a new attribute" autocomplete="off" spellcheck="false">
                                    <button type="button" data-mlv-add-attr>Add</button>
                                </label>
                                <datalist id="mlv-attribute-names">
                                    @foreach (array_unique([...array_keys($props), 'class', 'id', 'href', 'type', 'disabled', 'target', 'title', 'wire:click', 'x-on:click']) as $suggestion)
                                        <option value="{{ $suggestion }}"></option>
                                    @endforeach
                                </datalist>
                                @if ($props !== [])
                                    <div class="mlv-snippets">
                                        <span>Props</span>
                                        @foreach ($props as $prop => $default)
                                            <button type="button" data-mlv-prop="{{ $prop }}" data-mlv-default="{{ $default }}" title="Add the prop {{ $prop }}">{{ $prop }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </details>
                    </form>

                    <script type="application/json" data-mlv-slots>@json($slots)</script>
                    <script type="application/json" data-mlv-slot-classes>@json($slotClasses)</script>
                    <script type="application/json" data-mlv-snippets>@json($snippets)</script>
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
                        <span data-mlv-theme-target data-mlv-matrix-theme>
                            <button class="mlv-icon" type="button" data-mlv-card-theme title="Dark / light for this table">{!! $icon('moon', 'i-moon') !!}{!! $icon('sun', 'i-sun') !!}</button>
                        </span>
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
                                            <td class="mlv-stage" data-mlv-canvas data-mlv-matrix-cell title="{{ $cell->tag }}" data-mlv-copy="{{ $cell->tag }}">
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

<dialog class="mlv-dialog" data-mlv-dialog aria-labelledby="mlv-dialog-title">
    <header>
        <h2 class="mono" id="mlv-dialog-title" data-mlv-dialog-title></h2>
        <div class="mlv-tabs" role="tablist">
            <button type="button" role="tab" data-mlv-tab="blade" aria-selected="true">Blade</button>
            <button type="button" role="tab" data-mlv-tab="html" aria-selected="false">HTML</button>
        </div>
        <button class="mlv-icon" type="button" data-mlv-close title="Close (Esc)">{!! $icon('close') !!}</button>
    </header>
    <div class="mlv-code"><pre class="mono" data-mlv-dialog-code></pre></div>
    <footer>
        <span data-mlv-dialog-note></span>
        <button class="mlv-button" type="button" data-mlv-dialog-copy>{!! $icon('copy') !!} <span>Copy</span></button>
    </footer>
</dialog>

<div class="mlv-toast" role="status" aria-live="polite" data-mlv-toast></div>

<script>
    (() => {
        const root = document.documentElement;
        const store = (key, value) => { try { localStorage.setItem(key, value); } catch (e) {} };
        const $ = (selector, scope = document) => scope.querySelector(selector);
        const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

        // Toast
        const toast = $('[data-mlv-toast]');
        let toastTimer;
        const say = (title, detail = '') => {
            toast.replaceChildren(document.createTextNode(title));
            if (detail) {
                const code = document.createElement('code');
                code.textContent = detail;
                toast.append(code);
            }
            toast.classList.add('is-shown');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('is-shown'), 1600);
        };

        // Copy: the check mark is a separate icon, so clicking again and again never sticks.
        const done = new WeakMap();
        const copy = async (text, button) => {
            try {
                await navigator.clipboard.writeText(text);
                say('Copied', text.length > 80 ? text.slice(0, 80) + '…' : text);
                if (button) {
                    button.classList.add('is-done');
                    clearTimeout(done.get(button));
                    done.set(button, setTimeout(() => button.classList.remove('is-done'), 1200));
                }
            } catch (e) {
                say('Copy failed');
            }
        };

        // Themes: the page theme sets the UI and every preview; a card's own button only its preview.
        const setDark = (target, dark) => {
            target.classList.toggle('is-dark', dark);
            const canvases = target.matches('[data-mlv-matrix-theme]') ? $$('[data-mlv-matrix-cell]') : $$('[data-mlv-canvas]', target);
            canvases.forEach((canvas) => canvas.classList.toggle('dark', dark));
        };
        const applyTheme = (theme) => {
            root.dataset.mlvUi = theme;
            $$('[data-mlv-theme-target]').forEach((target) => setDark(target, theme === 'dark'));
            sync();
        };
        const sync = () => {
            const pressed = (attribute, value) => $$(`[${attribute}]`).forEach((b) => b.setAttribute('aria-pressed', String(b.getAttribute(attribute) === value)));
            pressed('data-mlv-theme', root.dataset.mlvUi || 'light');
            pressed('data-mlv-stage', root.dataset.mlvStage || 'dots');
            pressed('data-mlv-size', root.dataset.mlvSize || 'm');
        };
        applyTheme(root.dataset.mlvUi || 'light');

        // Code dialog
        const dialog = $('[data-mlv-dialog]');
        const dialogCode = $('[data-mlv-dialog-code]');
        let source = { blade: '', html: '' };
        let tab = 'blade';
        const escape = (text) => text.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]);
        const VOID = /^(area|base|br|col|embed|hr|img|input|link|meta|source|track|wbr)$/i;
        const format = (html) => {
            const parts = html.replace(/>\s+</g, '><').split(/(<!--[\s\S]*?-->|<[^>]+>)/).map((part) => part.trim()).filter(Boolean);
            const isOpening = (part) => /^<[a-z]/i.test(part) && ! /\/>$/.test(part) && ! VOID.test((part.match(/^<([a-z0-9-]+)/i) || [])[1] || '');
            const lines = [];
            let depth = 0;
            for (let i = 0; i < parts.length; i++) {
                const part = parts[i];
                // <b>text</b> stays on one line
                if (isOpening(part) && parts[i + 1] && ! parts[i + 1].startsWith('<') && /^<\//.test(parts[i + 2] || '')) {
                    lines.push('  '.repeat(depth) + part + parts[i + 1] + parts[i + 2]);
                    i += 2;
                    continue;
                }
                if (/^<\//.test(part)) depth = Math.max(0, depth - 1);
                lines.push('  '.repeat(depth) + part);
                if (isOpening(part)) depth++;
            }
            return lines.join('\n');
        };
        // One pass, so the spans it adds are never matched again.
        const highlight = (code) => escape(code).replace(
            /(&lt;!--[\s\S]*?--&gt;)|(\{\{[\s\S]*?\}\}|\{[!]![\s\S]*?![!]\}|@[a-zA-Z]+)|(&lt;\/?)([\w:.-]+)|([\w:@.-]+)=("[^"]*")|(\/?&gt;)/g,
            (match, comment, echo, open, tag, attribute, value, close) => {
                if (comment) return `<span class="tok-comment">${comment}</span>`;
                if (echo) return `<span class="tok-echo">${echo}</span>`;
                if (tag) return `<span class="tok-punct">${open}</span><span class="tok-tag">${tag}</span>`;
                if (close) return `<span class="tok-punct">${close}</span>`;
                return `<span class="tok-attr">${attribute}</span>=<span class="tok-str">${value}</span>`;
            },
        );
        // A tag as people write it: every child on its own line.
        const formatTag = (tag) => format(tag.replace(/\n\s*/g, ' ')).replace(/^( +)/gm, (spaces) => spaces.replace(/ {2}/g, '    '));
        const show = () => {
            $$('[data-mlv-tab]').forEach((b) => b.setAttribute('aria-selected', String(b.dataset.mlvTab === tab)));
            const text = tab === 'html' ? format(source.html) : source.blade;
            dialogCode.innerHTML = highlight(text || '(nothing rendered)');
            $('[data-mlv-dialog-note]').textContent = tab === 'html' ? 'What the browser receives, formatted.' : 'The tag to put in your views.';
        };
        const openCode = (title, blade, html) => {
            source = { blade, html };
            $('[data-mlv-dialog-title]').textContent = title;
            show();
            dialog.showModal();
        };
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

        // Playground
        const play = $('[data-mlv-play]');
        const json = (selector) => { try { return JSON.parse($(selector)?.textContent || '{}'); } catch (e) { return {}; } };
        const initialSlots = json('[data-mlv-slots]');
        const initialSlotClasses = json('[data-mlv-slot-classes]');
        const closeIcon = @json($icon('close'));
        const snippets = json('[data-mlv-snippets]');
        const initialBlade = $('[data-mlv-blade]')?.value ?? '';
        let mode = 'slots';
        let touched = false;
        let playTimer;
        let request = 0;

        const button = (className, title, html, data) => {
            const element = document.createElement('button');
            element.type = 'button';
            element.className = className;
            element.title = title;
            element.innerHTML = html;
            Object.assign(element.dataset, data);
            return element;
        };
        const slotRow = (name, text, slotClass = '') => {
            const row = document.createElement('div');
            row.className = 'mlv-slot';
            row.dataset.mlvSlotRow = name;
            const head = document.createElement('div');
            head.className = 'mlv-slot-head';
            const title = document.createElement('b');
            title.innerHTML = name === 'default' ? 'Main content <span>slot</span>' : '';
            if (name !== 'default') { title.append(name, ' '); const tag = document.createElement('span'); tag.textContent = 'slot'; title.append(tag); }
            head.append(title);
            const input = document.createElement(name === 'default' ? 'textarea' : 'input');
            input.className = 'mlv-input';
            input.dataset.mlvSlot = name;
            input.value = text;
            input.spellcheck = false;
            input.placeholder = name === 'default' ? 'Text inside the component' : `Text of ${name}`;
            row.append(head, input);
            if (name !== 'default') {
                const classInput = document.createElement('input');
                classInput.className = 'mlv-input mono';
                classInput.placeholder = `class on the ${name} slot`;
                classInput.spellcheck = false;
                classInput.dataset.mlvSlotClass = name;
                classInput.value = slotClass;
                classInput.hidden = slotClass === '';
                const toggle = button('mlv-mini', 'Add a class to this slot', 'class', { mlvToggleClass: '' });
                toggle.setAttribute('aria-pressed', String(slotClass !== ''));
                head.append(toggle, button('mlv-mini is-icon', 'Remove this slot', closeIcon, { mlvRemoveSlot: '' }));
                row.append(classInput);
            }
            return row;
        };
        const setSlots = (slots, classes = {}) => {
            const list = $('[data-mlv-slot-list]', play);
            list.replaceChildren(...Object.entries({ default: '', ...slots }).map(([name, text]) => slotRow(name, text, classes[name] || '')));
        };
        const readSlots = () => Object.fromEntries($$('[data-mlv-slot]', play).map((input) => [input.dataset.mlvSlot, input.value]));
        const readSlotClasses = () => Object.fromEntries($$('[data-mlv-slot-class]', play).map((input) => [input.dataset.mlvSlotClass, input.value]));

        // Attributes of the component: class, href, props, ...
        const attributeRow = (name, value) => {
            const row = document.createElement('label');
            row.className = 'mlv-attr';
            row.dataset.mlvAttrRow = name;
            const title = document.createElement('b');
            title.textContent = name;
            title.title = name;
            const input = document.createElement('input');
            input.className = 'mlv-input mono';
            input.dataset.mlvAttr = name;
            input.value = value;
            input.spellcheck = false;
            input.placeholder = name === 'class' ? 'w-full mt-4' : 'value';
            row.append(title, input, button('mlv-mini is-icon', 'Remove this attribute', closeIcon, { mlvRemoveAttr: '' }));
            return row;
        };
        const setAttributes = (attributes) => {
            $('[data-mlv-attr-list]', play).replaceChildren(...Object.entries(attributes).map(([name, value]) => attributeRow(name, value)));
            syncProps();
        };
        const readAttributes = () => Object.fromEntries($$('[data-mlv-attr]', play).map((input) => [input.dataset.mlvAttr, input.value]));
        const syncProps = () => {
            const props = $$('[data-mlv-prop]', play);
            props.forEach((b) => { b.disabled = !! $(`[data-mlv-attr-row="${CSS.escape(b.dataset.mlvProp)}"]`, play); });
            if (props.length) props[0].parentElement.hidden = props.every((b) => b.disabled);
            $('[data-mlv-attr-count]', play).textContent = $$('[data-mlv-attr]', play).length;
        };
        const addAttribute = (name, value = '') => {
            if (! /^[a-zA-Z][\w.:-]*$/.test(name)) { say('Attribute names start with a letter: href, id, wire:click'); return; }
            if ($$('[data-mlv-option]', play).some((group) => group.dataset.mlvOption === name)) { say(`${name} is an option - use its control above`); return; }
            if (! $(`[data-mlv-attr-row="${CSS.escape(name)}"]`, play)) $('[data-mlv-attr-list]', play).append(attributeRow(name, value));
            syncProps();
            $(`[data-mlv-attr="${CSS.escape(name)}"]`, play).focus();
            refresh();
        };

        // Blade <-> slot fields, so switching tabs keeps what was typed.
        // Built from pieces: Blade would compile a literal slot tag in this file.
        const SLOT_TAG = 'x-' + 'slot';
        const SLOT = new RegExp(`<${SLOT_TAG}(?::([\\w.-]+)|\\s+name=["']([\\w.-]+)["'])[^>]*>([\\s\\S]*?)</${SLOT_TAG}(?::[\\w.-]+)?\\s*>`, 'g');
        const toSlots = (blade) => {
            const named = {};
            const classes = {};
            const rest = blade.replace(SLOT, (m, a, b, inner) => {
                named[a || b] = inner.trim();
                const found = m.match(/^[^>]*\bclass=["']([^"']*)["']/);
                if (found) classes[a || b] = found[1];
                return '';
            });
            return [{ default: rest.replace(/\s*\n\s*/g, ' ').trim(), ...named }, classes];
        };
        const toBlade = (slots, classes) => Object.entries(slots)
            .filter(([name, text]) => name !== 'default' && (text.trim() !== '' || (classes[name] || '').trim() !== ''))
            .map(([name, text]) => `<${SLOT_TAG}:${name}${(classes[name] || '').trim() ? ` class="${classes[name].trim()}"` : ''}>${text}</${SLOT_TAG}:${name}>`)
            .concat((slots.default || '').trim() === '' ? [] : [slots.default.trim()])
            .join('\n');

        const showPane = (next) => {
            const blade = $('[data-mlv-blade]', play);
            if (next === 'blade' && mode === 'slots' && touched && blade) { blade.value = toBlade(readSlots(), readSlotClasses()); paint(); }
            if (next === 'slots' && mode === 'blade' && touched && blade) setSlots(...toSlots(blade.value));
            mode = next;
            $$('[data-mlv-mode]', play).forEach((b) => b.setAttribute('aria-selected', String(b.dataset.mlvMode === mode)));
            $$('[data-mlv-pane]', play).forEach((pane) => { pane.hidden = pane.dataset.mlvPane !== mode; });
        };

        const refresh = () => {
            clearTimeout(playTimer);
            playTimer = setTimeout(async () => {
                const values = {};
                $$('[data-mlv-option]', play).forEach((group) => {
                    const value = $('[aria-pressed="true"]', group)?.dataset.mlvValue;
                    if (value) values[group.dataset.mlvOption] = value;
                });
                const blade = $('[data-mlv-blade]', play);
                const body = {
                    component: play.dataset.mlvComponent,
                    values,
                    mode: touched ? mode : 'example',
                    slots: readSlots(),
                    slotClasses: readSlotClasses(),
                    attributes: readAttributes(),
                    blade: blade ? blade.value : '',
                };

                const id = ++request;
                play.classList.add('is-loading');
                try {
                    const response = await fetch(play.dataset.mlvUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(body),
                    });
                    if (! response.ok) throw new Error(`HTTP ${response.status}`);
                    const cell = await response.json();
                    if (id !== request) return;
                    const stage = $('[data-mlv-play-stage]', play);
                    if (cell.error) {
                        const error = document.createElement('span');
                        error.className = 'mlv-error';
                        error.textContent = cell.error;
                        stage.replaceChildren(error);
                    } else {
                        stage.innerHTML = cell.html;
                    }
                    showTag(cell.tag);
                    $('[data-mlv-source]', play).value = cell.html || '';
                } catch (e) {
                    say('Preview failed', e.message === 'HTTP 419' ? 'the page expired - reload it' : e.message);
                } finally {
                    if (id === request) play.classList.remove('is-loading');
                }
            }, 150);
        };

        const showTag = (tag) => {
            const formatted = formatTag(tag);
            $('[data-mlv-play-tag]', play).innerHTML = highlight(formatted);
            $('[data-mlv-play-copy]', play).dataset.mlvCopy = formatted;
        };

        // Blade editor highlighting
        const editor = $('[data-mlv-blade]', play || document);
        const paint = () => {
            const layer = $('[data-mlv-highlight]', play);
            if (! editor || ! layer) return;
            // A trailing newline needs a character after it, or the last line collapses.
            layer.innerHTML = highlight(editor.value) + '\n ';
            layer.scrollTop = editor.scrollTop;
        };
        editor?.addEventListener('input', paint);
        editor?.addEventListener('scroll', () => { $('[data-mlv-highlight]', play).scrollTop = editor.scrollTop; });

        if (play) {
            showTag($('[data-mlv-play-tag]', play).textContent);
            paint();
            setSlots(initialSlots, initialSlotClasses);
            setAttributes({ class: '' });

            play.addEventListener('input', (event) => {
                if (event.target.closest('[data-mlv-slot], [data-mlv-slot-class], [data-mlv-blade]')) touched = true;
                const adder = event.target.closest('.mlv-adder');
                if (adder) { adder.classList.toggle('has-value', event.target.value.trim() !== ''); return; }
                refresh();
            });

            // Tab indents in the Blade editor
            $('[data-mlv-blade]', play)?.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab') return;
                event.preventDefault();
                event.target.setRangeText('    ', event.target.selectionStart, event.target.selectionEnd, 'end');
                event.target.dispatchEvent(new Event('input', { bubbles: true }));
            });

            const addSlot = () => {
                const input = $('[data-mlv-new-slot]', play);
                const name = input.value.trim().replace(/\s+/g, '-');
                if (! /^[a-zA-Z][\w-]*$/.test(name) || name === 'default') { say('Slot names start with a letter: title, footer, icon'); return; }
                if (! $(`[data-mlv-slot-row="${name}"]`, play)) $('[data-mlv-slot-list]', play).append(slotRow(name, ''));
                input.value = '';
                $(`[data-mlv-slot="${name}"]`, play).focus();
            };
            $('[data-mlv-new-slot]', play).addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); addSlot(); } });
            const addTypedAttribute = () => { const input = $('[data-mlv-new-attr]', play); const name = input.value.trim(); if (name === '') return; input.value = ''; addAttribute(name); };
            $('[data-mlv-new-attr]', play).addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); addTypedAttribute(); } });
            play.addEventListener('click', (event) => {
                if (event.target.closest('[data-mlv-add-slot]')) addSlot();
                if (event.target.closest('[data-mlv-add-attr]')) addTypedAttribute();
                const prop = event.target.closest('[data-mlv-prop]');
                if (prop) addAttribute(prop.dataset.mlvProp, prop.dataset.mlvDefault || '');
                if (event.target.closest('[data-mlv-remove-attr]')) { event.target.closest('.mlv-attr').remove(); syncProps(); refresh(); }
                if (event.target.closest('[data-mlv-remove-slot]')) { event.target.closest('.mlv-slot').remove(); touched = true; refresh(); }
                const toggle = event.target.closest('[data-mlv-toggle-class]');
                if (toggle) {
                    const input = $('[data-mlv-slot-class]', toggle.closest('.mlv-slot'));
                    input.hidden = ! input.hidden;
                    toggle.setAttribute('aria-pressed', String(! input.hidden));
                    if (input.hidden) { if (input.value !== '') { input.value = ''; touched = true; refresh(); } } else input.focus();
                }
                const modeButton = event.target.closest('[data-mlv-mode]');
                if (modeButton) { showPane(modeButton.dataset.mlvMode); if (touched) refresh(); }
                const snippet = event.target.closest('[data-mlv-snippet]');
                if (snippet) {
                    const editor = $('[data-mlv-blade]', play);
                    const before = editor.value.slice(0, editor.selectionStart);
                    const text = (before === '' || before.endsWith('\n') ? '' : '\n') + (snippets[snippet.dataset.mlvSnippet] || '');
                    editor.focus();
                    editor.setRangeText(text, editor.selectionStart, editor.selectionEnd, 'end');
                    editor.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (event.target.closest('[data-mlv-reset]')) {
                    $$('[data-mlv-option]', play).forEach((group) => $$('button', group).forEach((b, i) => b.setAttribute('aria-pressed', String(i === 0))));
                    setSlots(initialSlots, initialSlotClasses);
                    setAttributes({ class: '' });
                    const blade = $('[data-mlv-blade]', play);
                    if (blade) { blade.value = initialBlade; paint(); }
                    touched = false;
                    refresh();
                }
            });

            // Resizable controls: drag the divider, or focus it and use the arrow keys.
            const resize = $('[data-mlv-resize]', play);
            const setWidth = (width) => {
                const max = Math.max(260, play.getBoundingClientRect().width * .65);
                const value = Math.round(Math.min(max, Math.max(240, width)));
                play.style.setProperty('--controls', `${value}px`);
                return value;
            };
            try { const saved = parseInt(localStorage.getItem('malevich.controls'), 10); if (saved) setWidth(saved); } catch (e) {}
            resize.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                resize.setPointerCapture(event.pointerId);
                play.classList.add('is-resizing');
                const right = play.getBoundingClientRect().right;
                const move = (e) => setWidth(right - e.clientX - 4);
                const up = () => {
                    play.classList.remove('is-resizing');
                    resize.removeEventListener('pointermove', move);
                    store('malevich.controls', parseInt(getComputedStyle(play).getPropertyValue('--controls'), 10));
                };
                resize.addEventListener('pointermove', move);
                resize.addEventListener('pointerup', up, { once: true });
                resize.addEventListener('pointercancel', up, { once: true });
            });
            resize.addEventListener('keydown', (event) => {
                const step = event.shiftKey ? 64 : 16;
                const current = parseInt(getComputedStyle(play).getPropertyValue('--controls'), 10) || 340;
                if (event.key === 'ArrowLeft') store('malevich.controls', setWidth(current + step));
                if (event.key === 'ArrowRight') store('malevich.controls', setWidth(current - step));
            });
            resize.addEventListener('dblclick', () => store('malevich.controls', setWidth(340)));
        }

        // Clicks
        document.addEventListener('click', (event) => {
            const target = event.target.closest('[data-mlv-copy], [data-mlv-code], [data-mlv-theme], [data-mlv-stage], [data-mlv-size], [data-mlv-card-theme], [data-mlv-tab], [data-mlv-close], [data-mlv-dialog-copy], [data-mlv-value]');

            if (! target) {
                // Previews are live, but a link or a submit button must not leave the page.
                if (event.target.closest('.mlv-stage') && event.target.closest('a[href], [type=submit]')) event.preventDefault();
                return;
            }

            if (target.matches('[data-mlv-value]')) {
                $$('button', target.parentElement).forEach((b) => b.setAttribute('aria-pressed', String(b === target)));
                refresh();
            } else if (target.matches('button[data-mlv-theme]')) {
                store('malevich.theme', target.dataset.mlvTheme);
                applyTheme(target.dataset.mlvTheme);
            } else if (target.matches('button[data-mlv-stage]')) {
                root.dataset.mlvStage = target.dataset.mlvStage;
                store('malevich.stage', target.dataset.mlvStage);
                sync();
            } else if (target.matches('button[data-mlv-size]')) {
                root.dataset.mlvSize = target.dataset.mlvSize;
                store('malevich.size', target.dataset.mlvSize);
                sync();
            } else if (target.matches('[data-mlv-card-theme]')) {
                const card = target.closest('[data-mlv-theme-target]');
                setDark(card, ! card.classList.contains('is-dark'));
            } else if (target.matches('[data-mlv-code]')) {
                const card = target.closest('.mlv-card, [data-mlv-play]');
                const tag = $('[data-mlv-copy]', card)?.dataset.mlvCopy || '';
                openCode(card.dataset.mlvLabel || tag, tag, $('[data-mlv-source]', card)?.value || '');
            } else if (target.matches('[data-mlv-tab]')) {
                tab = target.dataset.mlvTab;
                show();
            } else if (target.matches('[data-mlv-close]')) {
                dialog.close();
            } else if (target.matches('[data-mlv-dialog-copy]')) {
                copy(tab === 'html' ? format(source.html) : source.blade, target);
            } else if (target.matches('[data-mlv-copy]')) {
                event.preventDefault();
                copy(target.dataset.mlvCopy, target.matches('button') ? target : null);
            }
        });

        // Search: "/" focuses it, Enter opens the first match, Esc clears it
        const search = $('[data-mlv-search]');
        const items = $$('[data-mlv-item]');
        const nothing = $('[data-mlv-nothing]');
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

        // Highlight the section in view
        const links = new Map($$('.mlv-jump a').map((a) => [a.hash.slice(1), a]));
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                links.forEach((a) => a.removeAttribute('aria-current'));
                links.get(entry.target.id)?.setAttribute('aria-current', 'true');
            });
        }, { rootMargin: '-64px 0px -60% 0px' });
        links.forEach((a, id) => { const section = document.getElementById(id); if (section) observer.observe(section); });

        $('.mlv-nav [aria-current]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        sync();
    })();
</script>
</body>
</html>
