# Kit

Add the ready-made button to an app with Malevich and Tailwind CSS 4:

```bash
php artisan malevich:add button
```

This copies `button.blade.php` into `malevich.components.path` (by default, `resources/views/components`) and prints the component tag, respecting `malevich.components.prefix`. The copied file belongs to your project: edit it freely; package updates do not replace it. An existing file is preserved unless you explicitly pass `--force`. Without a name the command lets you pick a kit component from a list; an unknown name prints the available ones.

```bash
php artisan malevich:add button --force
```

Include the destination in Tailwind's source scanning and publish the [theme tokens](#theme-tokens) the kit uses. No JavaScript or additional components are needed beyond the Malevich primitive. `make:malevich` creates an empty scaffold; `malevich:add` copies a complete kit component.

The repository playground registers this directory automatically and defines the theme tokens below: run `composer serve` and open `/malevich`.

## Button

```blade
<x-button>Continue</x-button>
<x-button variant="outline" color="secondary">Cancel</x-button>
<x-button variant="soft" color="danger" size="sm">Delete</x-button>
<x-button variant="ghost" color="accent">Explore</x-button>
<x-button href="/docs" variant="link">Documentation</x-button>
```

| Prop | Values | Default |
| --- | --- | --- |
| `variant` | `solid`, `soft`, `surface`, `elevated`, `outline`, `ghost`, `link` | `solid` |
| `color` | `primary`, `secondary`, `accent`, `neutral`, `contrast`, `success`, `warning`, `danger`, `info` | `primary` |
| `size` | `xs`, `sm`, `md`, `lg`, `xl` | `md` |
| `radius` | `brand`, `none`, `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `full`, `squircle` | `brand` |
| `inset` | boolean; compensate horizontal padding for text buttons with `ghost` or `link` treatment | `false` |
| `block` | boolean; stretch the button to the full width of its container | `false` |
| `square` | boolean; force a square icon button on or off, detected from the default slot when omitted | `null` |
| `loading` | boolean | `false` |
| `loadingLabel` | named slot with replacement text, HTML or components; empty slot hides content | omitted |
| `disabled` | boolean | `false` |
| `leading` | leading icon content, usually a named slot | `null` |
| `trailing` | trailing icon content, usually a named slot | `null` |
| `as` | HTML tag | `button` |

All component props and named slots are declared at the top of the file. Use named slots for rich content and scoped attributes such as `spinner:size` to configure internal elements. Native HTML attributes (`type`, `name`, `value`, `href`, `target`, `aria-*`, `wire:*`, `x-*`) pass through the attribute bag. An `href` renders a link; disabled and loading links lose their destination.

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

### Aligning transparent buttons

```blade
<x-button variant="ghost" inset>Edit profile</x-button>
<x-button variant="link" size="sm" inset>Read more</x-button>
```

`inset` applies negative horizontal margins matching the button's horizontal padding. This compensates the transparent padding when placing the label alongside surrounding content, while retaining the padded hit area. It applies to `ghost` and `link` with a text label, regardless of color. The component expresses this as one `@cases('size', ..., when: [...])` map, adding margins without replacing the normal dimensions. Treatments with a visible background or border and automatically detected square icon buttons do not receive the adjustment. Use `:inset="false"` to disable it dynamically.

### Treatments

```blade
<x-button variant="surface" color="primary">Upgrade</x-button>
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

`elevated` keeps the same white background for every color; on dark themes it is a translucent white (`bg-white/5`), so it reads as raised on any dark surface.

### Colors

Variant and color are independent. Colors describe semantic roles:

| Role | Tailwind color scale | Purpose |
| --- | --- | --- |
| `primary` | `primary` | main action |
| `secondary` | `secondary` | supporting action |
| `accent` | `accent` | optional accent distinct from the main brand action |
| `neutral` | `gray` | monochrome; solid is light on light themes and dark on dark themes |
| `contrast` | `gray` | contrasting monochrome; solid is dark on light themes and light on dark themes |
| `success` | `success` | positive result |
| `warning` | `warning` | caution |
| `danger` | `danger` | destructive action |
| `info` | `info` | informational action |

#### Contrast

With the default [theme tokens](#theme-tokens), every text color has at least 4.5:1 contrast with its background (WCAG AA) on white, `gray-950` and `gray-900` pages, in every treatment and on hover, with one deliberate exception: the orange `primary`. White on `orange-600` is 3.58:1 and `primary-700` text on the `primary-200` hover of `soft` is 3.84:1. That meets the 3:1 for large text and UI components, not AA for button-sized text; the brighter orange was chosen over the deeper `700`. Focus outlines have at least 3:1 against the page. That is why the shades differ per role:

- `solid`: brand roles and `danger` use `600`; `success` and `info` are light hues, so they need `700` to carry white text. `warning` is `400` yellow with dark `warning-950` text.
- Text of every other treatment: `700` for the brand roles, `800` for the statuses, `300` on dark themes.
- Dark themes: `soft`, `surface` and the hover of `outline` and `ghost` use a translucent tint (`bg-success-400/15`) instead of a dark shade, so they stay visible on any dark surface, not only on pure `gray-950`.

If you point a role at another color in `malevich-theme.css`, check its contrast: a lighter hue may need the next darker shade. For full AA with an orange `primary`, change its solid case to `bg-primary-700 text-white hover:bg-primary-800` and its text to `text-primary-800`, as the statuses do.

The component uses your application's named Tailwind color scales: for example, `bg-primary-600`, `text-danger-800` and `border-success-300`. Define the seven semantic scales in your Tailwind theme, including the shades used by the component. `neutral` and `contrast` both use `gray-*` utilities. Backgrounds, text, borders, hover and focus styles all use the same named scales; the component does not assign them to built-in hues.

#### Theme tokens

The kit ships these tokens in [`theme.css`](theme.css): every role mapped onto one of Tailwind's built-in colors (`primary` is `orange`, `secondary` the warm grey `stone`, `accent` `violet`; the statuses are `green`, `yellow`, `red` and `blue`), `gray` remapped to the pure grey `neutral`, and `brand` set to `rounded-xl`. Publish it and import it after Tailwind:

```bash
php artisan vendor:publish --tag=malevich:theme
```

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import './malevich-theme.css';
```

The published `resources/css/malevich-theme.css` is yours: point each role at your brand's colors there. `malevich:add` reminds you about it until the file exists. Prefer to define the tokens your own way? Any `@theme` that provides `--color-primary-50` ... `--color-info-950` and `--radius-brand` works:

```css
@theme inline {
    --radius-brand: var(--radius-lg);

    --color-primary-50: var(--color-orange-50);
    /* ... every shade from 50 to 950, for each of the seven roles */
}
```

Without these tokens the button renders without colors or rounded corners: Tailwind silently skips utilities such as `bg-primary-600` it has no theme value for.

```blade
<x-button color="neutral">Cancel</x-button>
<x-button color="contrast">Continue</x-button>
```

`contrast` has its own styling for every variant. `solid` reverses the theme's surface brightness; `soft` uses a translucent dark tint on light themes and a light tint on dark themes. `outline` uses a stronger border, while `ghost` and `link` use stronger foreground colors. Soft, outline and ghost treatments also have their own theme-aware hover colors. Text remains readable against each treatment's background.

Customize a color's literal classes in the `@cases('color', [...], when: ['variant' => ...])` block of its treatment and its focus outline in `@color`. Each treatment has one block: solid, soft, surface and outline backgrounds and borders, the text color of every treatment but solid, and the hover tint of outline and ghost. `elevated` is the same for every color, so its classes live in `@variant`. Sizes use `@cases('size', ...)`: each size groups the root's dimensions and padding with the content, loading, leading, trailing and spinner classes. Each target still resolves its own size independently. No custom CSS variables, inline styles or generated class names are used.

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
