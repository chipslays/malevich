# Kit

Add the ready-made button to an app with Malevich and Tailwind CSS 4:

```bash
php artisan malevich:add button
```

This copies `button/index.blade.php` into `malevich.components.path` (by default, `resources/views/components`) and prints the component tag, respecting `malevich.components.prefix`. The copied file belongs to your project: edit it freely; package updates do not replace it. An existing file is preserved unless you explicitly pass `--force`. Without a name the command lets you pick a kit component from a list; an unknown name prints the available ones.

```bash
php artisan malevich:add button --force
```

Include the destination in Tailwind's source scanning and publish the [theme tokens](#theme-tokens) the kit uses. Buttons need no JavaScript. A component built from other kit components brings them along: `input`, `select` and `slider` also add `field`, unless you already have one. `make:malevich` creates an empty scaffold; `malevich:add` copies a complete kit component.

The kit also includes **input, select, field, switch, checkbox, radio, badge, progress, divider and slider**. See the [controls guide](controls.md) for variants, named elements, form bindings and examples. All components use the same palette and live in their own folders.

The repository playground registers this directory automatically and defines the theme tokens below: run `composer serve` and open `/malevich`.

Composed desktop examples are available at `/examples`, `/examples/projects` and `/examples/settings`. See the [workbench guide](../../workbench/README.md) and [composition components](composition.md) for avatar, typography and tabs.

## Button

```blade
<x-button>Continue</x-button>
<x-button variant="outline" color="neutral">Cancel</x-button>
<x-button variant="soft" color="danger" size="sm">Delete</x-button>
<x-button variant="ghost" color="brand">Explore</x-button>
<x-button href="/docs" variant="link">Documentation</x-button>
```

| Prop | Values | Default |
| --- | --- | --- |
| `variant` | `solid`, `soft`, `surface`, `elevated`, `outline`, `ghost`, `link` | `solid` |
| `color` | `brand`, `neutral`, `contrast`, `success`, `info`, `warning`, `danger` | `brand` |
| `size` | `xs`, `sm`, `md`, `lg`, `xl` | `md` |
| `radius` | `brand`, `none`, `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `full`, `squircle` | `brand` |
| `block` | boolean; stretch the button to the full width of its container | `false` |
| `square` | boolean; force a square icon button on or off, detected from the default slot when omitted | `null` |
| `loading` | boolean | `false` |
| `loadingLabel` | named slot with replacement text, HTML or components; empty slot hides content | omitted |
| `disabled` | boolean | `false` |
| `leading` | leading icon content, usually a named slot | `null` |
| `trailing` | trailing icon content, usually a named slot | `null` |
| `as` | HTML tag | `button` |

All component props and named slots are declared at the top of the file. Use named slots for rich content and scoped attributes such as `spinner:size` to configure internal elements. Native HTML attributes (`type`, `name`, `value`, `href`, `target`, `aria-*`, `wire:*`, `x-*`) pass through the attribute bag. An `href` renders a link; disabled and loading links lose their destination.

### Button group

Install the wrapper together with its button dependency:

```bash
php artisan malevich:add button.group
```

```blade
<x-button.group aria-label="Pagination">
    <x-button color="neutral" variant="outline">Previous</x-button>
    <x-button color="neutral" variant="outline">Next</x-button>
</x-button.group>

<x-button.group orientation="vertical" block aria-label="Actions">
    <x-button color="contrast">Save</x-button>
    <x-button color="neutral">Cancel</x-button>
</x-button.group>
```

The group joins direct child buttons and links with no gap, keeps only the outer corners rounded and overlaps adjacent borders by one pixel. A subtle separator remains between solid buttons. Each button keeps its color, variant, size, radius, action and disabled/loading state. Use consistent sizes for a continuous silhouette. A single button keeps all its corners. Hovered and keyboard-focused buttons rise above adjacent borders; buttons do not shift vertically when pressed inside a group.

| Prop | Values | Default |
| --- | --- | --- |
| `orientation` | `horizontal`, `vertical` | `horizontal` |
| `block` | boolean; full width, with equal-width buttons in horizontal groups | `false` |
| `as` | wrapper HTML tag | `div` |

The wrapper has `role="group"`; give it an `aria-label` when a name helps identify the group. Horizontal joining uses logical start/end edges, so it follows RTL direction. Put buttons directly inside the group without additional wrappers. This component groups independent actions; it does not add selection state or change keyboard navigation.

### Loading

`label` means the button's visible caption. Loading preserves it unless a replacement is explicitly provided:

```blade
{{-- Keep "Save changes"; the spinner appears to its left. --}}
<x-button loading>Save changes</x-button>

{{-- Replace the caption with text or markup. --}}
<x-button loading>
    <x-slot:loadingLabel class="font-semibold"><strong>Saving</strong> changes…</x-slot:loadingLabel>
    Save changes
</x-button>

{{-- Explicitly show only the spinner, preserving the original button dimensions. --}}
<x-button loading>
    <x-slot:loadingLabel></x-slot:loadingLabel>
    Save changes
</x-button>

{{-- Bind the state and render dynamic data inside the slot. --}}
<x-button :loading="$saving">
    <x-slot:loadingLabel>{{ $savingLabel }}</x-slot:loadingLabel>
    Save changes
</x-button>
```

With plain `loading`, the spinner replaces the leading icon, the original label remains visible, and the trailing icon stays. Without a leading icon, adding the spinner can increase the button's width.

With a `loadingLabel` slot, the original content stays in the layout with zero opacity and a loading layer overlays it. This preserves the original width and height. Long replacement labels are truncated to fit. An empty slot, whitespace or comments show just the spinner. Omitting the slot preserves the normal caption. Icon-only buttons always overlay a spinner without a label. The slot's HTML and nested Blade components render normally; its attributes are forwarded to the label wrapper. The name `loadingLabel` avoids a collision with the boolean `loading` prop.

Loading disables keyboard activation and navigation as well as pointer interaction. The original accessible label remains available when the visible content is hidden without replacement. With a replacement label, the original content is marked decorative. An explicit `aria-label` or `aria-labelledby` remains under the caller's control.

### Treatments

```blade
<x-button variant="surface" color="brand">Upgrade</x-button>
<x-button variant="elevated">Cancel</x-button>
```

| Variant | Looks like | Use it for |
| --- | --- | --- |
| `solid` | filled with the role's color | the main action |
| `soft` | a tint of the role | secondary actions that still carry the role |
| `surface` | a lighter tint with a border of the role | between `soft` and `outline`: buttons on cards and tables, where `soft` blends in and `outline` looks empty |
| `elevated` | a neutral raised button with a shadow; only the text takes the role | the classic secondary button next to a solid one ("Cancel" beside "Save") |
| `outline` | a border of the role | low-emphasis actions |
| `ghost` | text only, tinted on hover | toolbars, menus, icon buttons |
| `link` | text with an underline on hover | inline actions |

`elevated` uses `surface-raised` and `line` for every color, with a subtle tint of the selected color on hover.

### Colors

Choose a meaning with `color`, then choose its visual treatment with `variant`. Color names describe their relationship to the interface; they never mean a fixed light or dark shade.

| Color | Meaning | Solid in light / dark themes |
| --- | --- | --- |
| `brand` | the brand's signature color; default | orange / orange |
| `neutral` | a quiet button that belongs to the surface | near-white / charcoal |
| `contrast` | a monochrome button that stands out from the surface | charcoal / near-white |
| `success` | positive result | jade green / jade green |
| `info` | information | clear blue / clear blue |
| `warning` | caution | amber / amber, with warm brown text |
| `danger` | destructive or risky action | coral red / coral red |

```blade
<x-button>Save</x-button>
<x-button color="neutral">Cancel</x-button>
<x-button color="contrast">Continue</x-button>
<x-button color="warning" variant="soft">Review changes</x-button>
<x-button color="danger" variant="outline">Delete</x-button>
```

`neutral` and `contrast` are two positions on one monochrome ramp, configured with `--mono-light` and `--mono-dark`. Their hue and chroma are identical; only lightness differs. Solid fills and their matching text exchange the exact same endpoints when the theme changes. Muted ink is derived from those endpoints instead of picked independently. Surface and outline share one clean frame recipe for both roles, with a fine border and subtle hover/active fills. There are no `primary`, `secondary` or `accent` colors: importance depends on treatment and placement, while `brand` names the brand color.

#### Theme tokens

Publish the theme and import it after Tailwind CSS 4:

```bash
php artisan vendor:publish --tag=malevich:theme
```

```css
@import 'tailwindcss';
@import './vendor/malevich.css';
@custom-variant dark (&:where(.dark, .dark *));
```

Keep the complete published stylesheet. Edit its `:root` values for the light theme and `.dark` values for the dark theme. Toggle the `dark` class on the page or a local preview container. Brand and status pairs are shared across themes by default. The default palette uses custom OKLCH values: orange, jade green, clear blue, honey amber and coral red. Neutrals share the same subtle cool hue, and interaction states use small, consistent lightness shifts.

The system has two groups of public tokens:

| Token | Meaning | Classes |
| --- | --- | --- |
| `surface` | the page or section background | `bg-surface` |
| `surface-raised` | cards, menus and raised controls | `bg-surface-raised` |
| `ink` | main text and icons | `text-ink` |
| `ink-muted` | supporting text | `text-ink-muted` |
| `line` | dividers and borders | `border-line` |
| `focus` | keyboard focus indicator | `outline-focus`, `ring-focus` |
| `ROLE` | a filled color | `bg-brand`, `bg-warning`, etc. |
| `on-ROLE` | text and icons on that fill | `text-on-brand`, `text-on-warning`, etc. |
| `ROLE-ink` | the role expressed as text on a surface; derived automatically | `text-brand-ink`, `text-warning-ink`, etc. |

Pair a fill with its `on-` color. Use its `-ink` color when the background is a page, card or tinted surface:

```html
<section class="bg-surface text-ink">
    <article class="bg-surface-raised border border-line">
        <p class="text-ink-muted">Supporting information</p>
        <span class="bg-warning text-on-warning">Pending review</span>
        <a class="text-brand-ink">Read more</a>
    </article>
</section>
```

Changing a color takes one surface/ink pair, not eleven shades or seven separate variant maps:

```css
:root {
    --brand: var(--color-violet-500);
    --on-brand: white;
}
```

This updates brand buttons, their hover and active states, tinted backgrounds, outlines and brand text in both themes. Add the same pair to `.dark` only if the brand needs a separate dark-theme treatment. The built-in Tailwind color scales remain available when choosing literal shades.

#### Button internals and custom colors

`tone-brand` (or another `tone-ROLE`) selects three local values: `--tone` is the fill, `--tone-on` is content on that fill, and `--tone-ink` is colored text on a surface. `button-colors` derives the `--button-*` tokens used by all seven variants. Soft fills and borders mix with the current theme surface; solid hover and active states move gently toward the matching content color. No inline styles are generated.

To add a color, define its tone utility in the published theme and add its name to the button's `@cases('color', ...)` map:

```css
@utility tone-promotion {
    --tone: var(--color-violet-500);
    --tone-on: white;
    --tone-ink: color-mix(in oklab, var(--color-violet-500) 80%, var(--ink));
}
```

```blade
'promotion' => ['button' => ['tone-promotion']],
```

All variants use the new tone immediately. Define tone values on the button itself so a tone nested inside another tone never inherits the parent's resolved colors.

### Radius

`brand` uses `rounded-brand`, the default corner radius defined by your application's Tailwind theme. Configure the named `brand` radius in that theme. The remaining scale follows [Tailwind's border radius utilities](https://tailwindcss.com/docs/border-radius).

```blade
<x-button radius="brand">Continue</x-button>
<x-button radius="2xl">Continue</x-button>
<x-button radius="full">Continue</x-button>
<x-button radius="squircle">Continue</x-button>
```

`squircle` combines `rounded-2xl` with the Tailwind arbitrary property `[corner-shape:squircle]`, following the [CSS corner-shape specification](https://www.w3.org/TR/css-borders-4/#corner-shape). Browsers that do not implement corner shaping retain ordinary rounded corners.

### Icons and internal elements

Use the `leading` and `trailing` slots for decorative icons. Their attributes and classes apply to the corresponding wrappers; direct SVG children follow the selected size:

```blade
<x-button size="sm">
    <x-slot:leading size="lg">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14m-7-7h14" /></svg>
    </x-slot:leading>
    Add item
</x-button>

<x-button aria-label="Add item" radius="full">
    <x-slot:leading>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14m-7-7h14" /></svg>
    </x-slot:leading>
</x-button>

<x-button loading spinner:size="sm">Save changes</x-button>
```

Buttons without text in their default slot automatically become square icon buttons. Named icon slots, whitespace, comments and SVG-only content do not require a separate flag. When detection guesses wrong, for example with visually hidden text (`<span class="sr-only">`), set it yourself: `square` or `:square="false"`. A text label wrapped in markup still produces a normal button. Always give an icon-only button an accessible name using `aria-label` or `aria-labelledby`. The kit does not bundle an icon set. See [inner elements](../../docs/05-inner-elements.md) for configuring slots, internal elements and modifiers.

Root `class` and named-slot classes are appended last. Install a supported Tailwind merge integration when conflicting utilities should replace defaults; otherwise edit the maps to avoid conflicts.
