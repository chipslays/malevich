{{-- <x-tabs value="overview"><x-tabs.list aria-label="Project sections"><x-tabs.tab value="overview">Overview</x-tabs.tab><x-tabs.tab value="activity">Activity</x-tabs.tab></x-tabs.list><x-tabs.panel value="overview">Project overview.</x-tabs.panel><x-tabs.panel value="activity">Recent activity.</x-tabs.panel></x-tabs> --}}

@props(['value' => null, 'variant' => 'underline', 'color' => 'contrast', 'size' => 'md', 'orientation' => 'horizontal', 'nonce' => null])

@php($id = $attributes->get('id') ?? 'tabs-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)))

@base('tabs', 'tone-colors tabs-style flex min-w-0 gap-6 text-sm')
@cases('variant', ['underline' => ['tabs' => ['tabs-underline']], 'soft' => ['tabs' => ['tabs-soft']], 'solid' => ['tabs' => ['tabs-solid']]])
@cases('orientation', ['horizontal' => ['tabs' => ['flex-col']], 'vertical' => ['tabs' => ['tabs-vertical flex-row items-start']]])
@size('tabs', ['sm' => '[--tab-height:2rem] [--tab-padding:0.75rem]', 'md' => '[--tab-height:2.25rem] [--tab-padding:1rem]', 'lg' => '[--tab-height:2.5rem] [--tab-padding:1.25rem]'])
@cases('color', [
    'brand' => ['tabs' => ['tone-brand']], 'neutral' => ['tabs' => ['tone-neutral']],
    'contrast' => ['tabs' => ['tone-contrast']], 'success' => ['tabs' => ['tone-success']],
    'info' => ['tabs' => ['tone-info']], 'warning' => ['tabs' => ['tone-warning']], 'danger' => ['tabs' => ['tone-danger']],
])

<div @attributes('tabs', slot: $attributes, merge: ['id' => $id, 'data-tabs' => '', 'data-value' => $value, 'data-orientation' => $orientation])>{{ $slot }}</div>

@once
    <script @if ($nonce) nonce="{{ $nonce }}" @endif>
        (() => {
            const own = (root, selector) => [...root.querySelectorAll(selector)].filter((element) => element.closest('[data-tabs]') === root);
            const activate = (root, value, focus = false, announce = false) => {
                const tabs = own(root, '[role="tab"]');
                const panels = own(root, '[role="tabpanel"]');
                const selected = tabs.find((tab) => tab.dataset.value === value && !tab.disabled) || tabs.find((tab) => !tab.disabled);
                tabs.forEach((tab, index) => {
                    tab.id ||= root.id + '-tab-' + index;
                    const panel = panels.find((panel) => panel.dataset.value === tab.dataset.value);
                    if (panel) { panel.id ||= root.id + '-panel-' + index; tab.setAttribute('aria-controls', panel.id); panel.setAttribute('aria-labelledby', tab.id); }
                    tab.setAttribute('aria-selected', String(tab === selected));
                    tab.tabIndex = tab === selected ? 0 : -1;
                });
                panels.forEach((panel) => panel.hidden = !selected || panel.dataset.value !== selected.dataset.value);
                own(root, '[role="tablist"]').forEach((list) => list.setAttribute('aria-orientation', root.dataset.orientation));
                root.dataset.ready = '';
                if (selected) { root.dataset.active = selected.dataset.value; if (focus) selected.focus(); }
                if (announce && selected) root.dataset.interacted = 'true';
                if (announce && selected) root.dispatchEvent(new CustomEvent('tabs:change', { bubbles: true, detail: { value: selected.dataset.value } }));
            };
            const init = (root) => { if (!root.hasAttribute('data-ready')) activate(root, root.dataset.value); };
            const scan = (node) => { if (node.matches?.('[data-tabs]')) init(node); node.querySelectorAll?.('[data-tabs]').forEach(init); };
            document.addEventListener('click', (event) => {
                const tab = event.target.closest('[role="tab"][data-value]');
                const root = tab?.closest('[data-tabs]');
                if (root && !tab.disabled) activate(root, tab.dataset.value, false, true);
            });
            document.addEventListener('keydown', (event) => {
                const tab = event.target.closest('[role="tab"][data-value]');
                const root = tab?.closest('[data-tabs]');
                if (!root || event.altKey || event.ctrlKey || event.metaKey) return;
                const tabs = own(root, '[role="tab"]').filter((tab) => !tab.disabled);
                const index = tabs.indexOf(tab);
                let next;
                if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = tabs.length - 1;
                else {
                    const vertical = root.dataset.orientation === 'vertical';
                    const forward = vertical ? 'ArrowDown' : getComputedStyle(root).direction === 'rtl' ? 'ArrowLeft' : 'ArrowRight';
                    const backward = vertical ? 'ArrowUp' : getComputedStyle(root).direction === 'rtl' ? 'ArrowRight' : 'ArrowLeft';
                    if (event.key === forward) next = (index + 1) % tabs.length;
                    if (event.key === backward) next = (index - 1 + tabs.length) % tabs.length;
                }
                if (next !== undefined && tabs[next]) { event.preventDefault(); activate(root, tabs[next].dataset.value, true, true); }
            });
            document.addEventListener('invalid', (event) => {
                const panel = event.target.closest('[role="tabpanel"]');
                const root = panel?.closest('[data-tabs]');
                if (root && panel.hidden) activate(root, panel.dataset.value);
            }, true);
            document.addEventListener('tabs:select', (event) => {
                const root = event.target.closest('[data-tabs]');
                if (root) activate(root, String(event.detail?.value ?? ''), false, true);
            });
            document.addEventListener('DOMContentLoaded', () => scan(document), { once: true });
            new MutationObserver((records) => records.forEach((record) => {
                record.addedNodes.forEach(scan);
                const root = record.target.closest?.('[data-tabs]');
                if (root && [...record.addedNodes].some((node) => node.matches?.('[role="tab"], [role="tabpanel"], [data-tabs-list]'))) activate(root, root.dataset.interacted === 'true' ? root.dataset.active : root.dataset.value || root.dataset.active);
            })).observe(document.documentElement, { childList: true, subtree: true });
            scan(document);
        })();
    </script>
@endonce
