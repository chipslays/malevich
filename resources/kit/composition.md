# Composition components

```bash
php artisan malevich:add avatar heading text tabs panel dialog
```

These components use the same semantic colors and radius names as the rest of the kit. The default text scale is 14px. Large text is explicit; small metadata uses `size="xs"`. The workbench examples use Google Inter.

## Avatar

```blade
<x-avatar name="Алексей Морозов" />
<x-avatar src="/images/alex.jpg" name="Alex Morgan" size="lg" radius="full" />
<x-avatar name="Jane Doe" initials="JD" radius="lg" color="brand" status="online" />
<x-avatar alt="Guest"><x-icon name="lucide-user" class="size-4 shrink-0" /></x-avatar>
```

`name` generates initials from the first and last words, including Unicode names. `initials` overrides them; the default slot provides a completely custom fallback. An empty name shows `?`.

The fallback is visible immediately while `src` loads. A successful image fades in; a broken image leaves the fallback visible. Cached images, lazy images, `srcset` changes and dynamically inserted avatars are handled. Without JavaScript the fallback remains visible.

| Prop | Values / behavior | Default |
| --- | --- | --- |
| `src`, `srcset` | native image sources | omitted |
| `name` | full name, used for initials and the accessible label | omitted |
| `alt` | overrides the accessible label | `name` or initials |
| `initials` | explicit initials, up to three characters | derived |
| `size` | `xs`, `sm`, `md`, `lg`, `xl`, `2xl` → 24, 32, 40, 48, 64, 80px | `md` |
| `radius` | the exact button scale: `brand`, `none`, `xs`…`4xl`, `full`, `squircle` | `full` |
| `variant` | `soft`, `solid`, `outline` | `soft` |
| `color` | the seven standard roles | `neutral` |
| `status` | `online`, `away`, `busy`, `offline` | omitted |
| `loading` | `lazy`, `eager` | `lazy` |
| `nonce` | CSP nonce for the single shared behavior script | omitted |

Named elements: `avatar`, `fallback`, `image`, `status`. Ordinary attributes decorate the outer avatar; `image:class`, `image:referrerpolicy`, etc. decorate the actual image. `data-state` is `fallback`, `loading`, `loaded` or `error`. Image and fallback inherit the chosen radius, so they never have mismatched corners.

## Heading and text

```blade
<x-heading as="h1" size="xl">Workspace settings</x-heading>
<x-heading size="sm">Notifications</x-heading>
<x-text color="neutral">Choose what you want to hear about.</x-text>
<x-text as="span" size="xs">Updated today</x-text>
```

`as` controls semantics; `size` controls appearance. Heading defaults to `h2`, size `md` (20px), semibold. Its sizes are `xs` (16), `sm` (18), `md` (20), `lg` (24), `xl` (28), `2xl` (36).

Text defaults to `p`, size `sm` (14px). Sizes are `xs` (12), `sm` (14), `md` (16), `lg` (18). `weight` is `normal`, `medium` or `semibold`; `truncate` is optional. Both support the seven `color` roles: `contrast` for regular text, `neutral` for supporting text, and brand/status roles for intentional emphasis. Named roots are `heading` and `text`.

## Tabs

```blade
<x-tabs value="profile" variant="underline">
    <x-tabs.list aria-label="Settings">
        <x-tabs.tab value="profile">Profile</x-tabs.tab>
        <x-tabs.tab value="notifications">Notifications</x-tabs.tab>
        <x-tabs.tab value="billing" disabled>Billing</x-tabs.tab>
    </x-tabs.list>
    <x-tabs.panel value="profile">Profile form…</x-tabs.panel>
    <x-tabs.panel value="notifications">Notification settings…</x-tabs.panel>
    <x-tabs.panel value="billing">Billing settings…</x-tabs.panel>
</x-tabs>
```

`malevich:add tabs` installs the root, list, tab and panel together. Installing any part also installs its family, without replacing customized files. Keep the list and panels directly inside the tabs root. Values must be unique within one tabs instance and match between tab and panel.

| Prop | Values | Default |
| --- | --- | --- |
| `value` | initial selected value | first enabled tab |
| `variant` | `underline`, `soft`, `solid` | `underline` |
| `color` | standard semantic roles | `contrast` |
| `size` | `sm`, `md`, `lg` | `md` |
| `orientation` | `horizontal`, `vertical` | `horizontal` |
| `nonce` | CSP nonce for the shared behavior script | omitted |

Tabs have their own `disabled` prop. The script connects unique IDs and ARIA relationships, hides inactive panels and maintains a single tab stop. Arrows, Home and End select enabled tabs, including RTL and vertical navigation. Nested tabs are isolated. A required control in a hidden panel reveals its panel on native validation.

The root dispatches a bubbling `tabs:change` event with `event.detail.value`. To select a tab programmatically, dispatch `new CustomEvent('tabs:select', { detail: { value: 'profile' } })` on its root. User changes stay local; there is no implicit server request.

Named elements are `tabs`, `list`, `tab`, `panel`, each in its own corresponding component. Tabs require their small inline behavior script to switch panels; no framework is required.

## Panel

```blade
<x-panel title="Projects" description="What the team is working on">
    <x-slot:action><x-button>New project</x-button></x-slot:action>
    Your content…
    <x-slot:footer>Last updated today</x-slot:footer>
</x-panel>
```

`padding` is `none`, `sm`, `md` (default) or `lg`; `as` defaults to `section`. `padding="none"` is useful for tables and divided form sections. Named elements: `panel`, `header`, `heading`, `title`, `description`, `action`, `body`, `footer`.

## Dialog

```blade
<x-dialog id="new-project" title="New project" description="Start with the basics.">
    Your form…
    <x-slot:footer><x-button type="submit" form="project-form">Create</x-button></x-slot:footer>
</x-dialog>
```

Uses native `<dialog>`: call `document.getElementById('new-project').showModal()` to open it. Escape and the built-in close button dismiss it. `size` is `sm`, `md` (default), `lg` or `xl`. Named elements: `dialog`, `header`, `title`, `description`, `close`, `body`, `footer`. The workbench demonstrates open triggers and backdrop dismissal in its own page script.
