{{-- <x-avatar name="Alex Morgan" size="lg" /> --}}

@props([
    'src' => null,
    'srcset' => null,
    'name' => null,
    'alt' => null,
    'initials' => null,
    'size' => 'md',
    'radius' => 'full',
    'color' => 'neutral',
    'variant' => 'soft',
    'status' => null,
    'loading' => 'lazy',
    'nonce' => null,
])

{{-- State --}}
@php
    $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $letters = $initials ?? ($words ? mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : '') : '?');
    $letters = mb_strtoupper(mb_substr($letters, 0, 3));
    $hasImage = is_string($src) && trim($src) !== '';
    $accessibleName = $alt ?? $name ?? $letters;
    $hasFallback = $slot->hasActualContent();
@endphp

{{-- Styles --}}
@base('avatar', [
    'tone-colors relative inline-flex shrink-0 items-center justify-center align-middle select-none',
    'data-[state=loaded]:[&>img]:opacity-100 data-[state=loaded]:[&>.avatar-fallback]:opacity-0',
])
@base('fallback', 'avatar-fallback flex size-full items-center justify-center overflow-hidden rounded-[inherit] [corner-shape:inherit] border font-medium transition-opacity duration-200 motion-reduce:transition-none')
@base('image', 'absolute inset-0 size-full rounded-[inherit] [corner-shape:inherit] object-cover opacity-0 transition-opacity duration-200 motion-reduce:transition-none')
@base('status', 'absolute end-0 bottom-0 z-10 size-[28%] rounded-full border-2 border-surface-raised')
@cases('color', [
    'brand' => ['avatar' => ['tone-brand']],
    'neutral' => ['avatar' => ['tone-neutral']],
    'contrast' => ['avatar' => ['tone-contrast']],
    'success' => ['avatar' => ['tone-success']],
    'info' => ['avatar' => ['tone-info']],
    'warning' => ['avatar' => ['tone-warning']],
    'danger' => ['avatar' => ['tone-danger']],
])
@cases('variant', [
    'soft' => ['fallback' => ['border-transparent bg-(--tone-soft) text-(--tone-ink)']],
    'solid' => ['fallback' => ['border-transparent bg-(--tone) text-(--tone-on)']],
    'outline' => ['fallback' => ['border-line bg-surface-raised text-(--tone-ink)']],
])
@option('radius', 'avatar', [
    'brand' => 'rounded-brand',
    'none' => 'rounded-none',
    'xs' => 'rounded-xs',
    'sm' => 'rounded-sm',
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    '2xl' => 'rounded-2xl',
    '3xl' => 'rounded-3xl',
    '4xl' => 'rounded-4xl',
    'full' => 'rounded-full',
    'squircle' => ['rounded-2xl', '[corner-shape:squircle]'],
])
@size('avatar', ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-12 text-base', 'xl' => 'size-16 text-xl', '2xl' => 'size-20 text-2xl'])
@option('status', 'status', ['online' => 'bg-success', 'away' => 'bg-warning', 'busy' => 'bg-danger', 'offline' => 'bg-ink-muted'])

{{-- Markup --}}
<span @attributes('avatar', slot: $attributes, merge: ['role' => 'img', 'aria-label' => $accessibleName, 'data-avatar' => '', 'data-state' => $hasImage ? 'loading' : 'fallback'])>
    <span @attributes('fallback') aria-hidden="true">{{ $hasFallback ? $slot : $letters }}</span>
    @if ($hasImage)
        <img @attributes('image', merge: ['src' => $src, 'srcset' => $srcset, 'alt' => '', 'loading' => $loading === 'eager' ? 'eager' : 'lazy', 'decoding' => 'async', 'data-avatar-image' => '']) />
    @endif
    @if ($status !== null)<span @attributes('status') aria-hidden="true"></span>@endif
</span>

@once
    <script @if ($nonce) nonce="{{ $nonce }}" @endif>
        (() => {
            const isAvatar = (image) => image.matches?.('img[data-avatar-image]');
            const state = (image, value) => { const avatar = image.closest('[data-avatar]'); if (avatar) avatar.dataset.state = value; };
            const sync = (image) => { if (image.complete) state(image, image.naturalWidth > 0 ? 'loaded' : 'error'); };
            const scan = (node) => {
                if (isAvatar(node)) sync(node);
                node.querySelectorAll?.('img[data-avatar-image]').forEach(sync);
            };
            document.addEventListener('load', (event) => { if (isAvatar(event.target)) state(event.target, 'loaded'); }, true);
            document.addEventListener('error', (event) => { if (isAvatar(event.target)) state(event.target, 'error'); }, true);
            document.addEventListener('DOMContentLoaded', () => scan(document), { once: true });
            new MutationObserver((records) => records.forEach((record) => {
                if (record.type === 'attributes' && isAvatar(record.target)) { state(record.target, 'loading'); sync(record.target); }
                record.addedNodes.forEach(scan);
            })).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['src', 'srcset'] });
            scan(document);
        })();
    </script>
@endonce
