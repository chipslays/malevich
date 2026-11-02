# Form controls and indicators

Every component lives in its own folder with an `index.blade.php`, like `button`. All use `theme.css`: the same surface, ink, borders, seven color roles and corner radius. Nothing depends on a JavaScript framework.

```bash
php artisan malevich:add input select field switch checkbox radio badge progress divider slider
```

Import the complete published theme after Tailwind CSS 4 and include the installed components directory in source scanning. `input`, `select` and `slider` automatically install `field`, preserving an existing customized copy.

```css
@import 'tailwindcss';
@import './vendor/malevich.css';
@source '../views/components';
@custom-variant dark (&:where(.dark, .dark *));
```

## Shared API

`color` has the same meaning everywhere: `brand`, `neutral`, `contrast`, `success`, `info`, `warning`, `danger`. Color defaults to `brand`, except for `divider` (`neutral`). A neutral choice control or meter uses neutral ink for its selected indicator so it remains visible on a light surface.

| Component | Variants | Sizes | Named elements |
| --- | --- | --- | --- |
| `input` | `outline`, `soft`, `surface`, `ghost` | `xs`, `sm`, `md`, `lg`, `xl` | `field`, `input`, `control`, `leading`, `trailing` |
| `select` | `outline`, `soft`, `surface`, `ghost` | `xs`, `sm`, `md`, `lg`, `xl` | `field`, `select`, `control`, `indicator` |
| `field` | `layout="stacked"` or `inline` | — | `field`, `heading`, `label`, `required`, `body`, `description`, `error`, `trailing` |
| `checkbox` | `solid`, `soft` | `sm`, `md`, `lg` | `checkbox`, `control`, `indicator`, `mark`, `content`, `label`, `description`, `error` |
| `radio` | `solid`, `soft` | `sm`, `md`, `lg` | `radio`, `control`, `indicator`, `mark`, `content`, `label`, `description`, `error` |
| `switch` | `solid`, `soft` | `sm`, `md`, `lg` | `switch`, `control`, `indicator`, `thumb`, `content`, `label`, `description`, `error` |
| `badge` | `solid`, `soft`, `surface`, `outline`, `ghost` | `sm`, `md`, `lg` | `badge`, `content`, `dot`, `leading`, `trailing` |
| `progress` | `solid`, `soft`, `striped` | `sm`, `md`, `lg` | `progress`, `heading`, `label`, `value`, `track` |
| `divider` | `solid`, `dashed`, `dotted` | — | `divider`, `before`, `text`, `after` |
| `slider` | `solid`, `soft` | `sm`, `md`, `lg` | `field`, `slider`, `control`, `value` |

All sizes default to `md`. Form controls default to `outline`, badge to `soft`, other variants to `solid`. Input and select heights match button sizes: 28, 32, 36, 40 and 44 pixels. They support `radius="brand|none|sm|md|lg|xl|full"`; badge defaults to `full` and supports the same values except `xl`.

## Input

```blade
<x-input name="email" type="email" label="Email" placeholder="you@example.com"
    description="Use your work address." required />

<x-input name="amount" type="number" min="0" step="0.01" label="Amount">
    <x-slot:leading>$</x-slot:leading>
    <x-slot:trailing>USD</x-slot:trailing>
</x-input>

<x-input variant="soft" wire:model.live="search" placeholder="Search…"
    class="max-w-sm" control:class="font-mono" field:class="mt-4" />
```

`class` and `style` decorate the input box; `field:class` styles its outer field. Other attributes (`name`, `id`, `value`, `autocomplete`, `wire:model`, `x-model`, events and `aria-*`) go to the native input. `control:class` styles the actual input. `leading` and `trailing` accept text or named slots with any markup.

`disabled`, `readonly` and `required` preserve native browser behavior. An omitted `type` is `text`. Input supports all ordinary single-line native types; use checkbox, radio, switch and slider components for those specialized controls.

## Select

```blade
<x-select name="country" label="Country" placeholder="Choose a country"
    :options="['ru' => 'Russia', 'us' => 'United States']" :value="$country" />

<x-select name="regions[]" multiple label="Regions" :value="['eu', 'us']"
    :options="['Europe' => ['eu' => 'European Union'], 'us' => 'United States']" />

<x-select name="plan" label="Plan">
    <option value="basic">Basic</option>
    <option value="pro" @selected($plan === 'pro')>Professional</option>
</x-select>
```

Option keys are submitted values; labels are escaped. Nested arrays become optgroups. `value` selects options supplied by `options`; with native option slots, use `selected` / `@selected` yourself. `multiple` removes the decorative chevron and expands the control into a list. Include `[]` in its name when submitting an array. This is a native select, preserving browser and mobile picker behavior.

## Field and validation

Input, select and slider accept `label`, `description`, `error`, `invalid` and `disabled`. They generate a unique control ID, connect the label, and append hint/error IDs to existing `aria-describedby`. Labels, descriptions and errors can also be rich named slots. Errors are read from Laravel's `$errors` by name, including `user[email]` → `user.email`. `error="…"` sets a message; `:error="true"` marks invalid without text; `:error="false"` suppresses automatic messages. `invalid` only changes the state.

Use `field` directly for other controls or groups. Supply `for` to associate a label with your custom control; a field cannot infer IDs inside arbitrary slot markup.

```blade
<x-field label="Notes" for="notes" description="Optional details" layout="inline">
    <textarea id="notes" name="notes" class="rounded-brand border border-line bg-surface-raised p-3 text-ink"></textarea>
</x-field>

<x-field as="fieldset" label="Plan" error="Choose a plan">
    <x-radio name="plan" value="basic">Basic</x-radio>
    <x-radio name="plan" value="pro">Professional</x-radio>
</x-field>
```

Field uses native `fieldset` / `legend` semantics when `as="fieldset"`. Native attributes such as `disabled` pass through. Its default element is `div`. The `trailing` slot can place a count or action beside the label.

## Checkbox, radio and switch

```blade
<x-checkbox name="terms" value="accepted" required>I accept the terms</x-checkbox>
<x-checkbox label="Select all" indeterminate />

<x-radio name="plan" value="basic" checked label="Basic" />
<x-radio name="plan" value="pro" label="Professional" description="For growing teams" />

<x-switch name="notifications" checked label="Notifications"
    description="Receive important updates" wire:model="notifications" />
```

Each is a native input inside a label. The visible indicator mirrors checked, disabled and keyboard focus states; switch thumb movement follows RTL direction. Use `label` or the default slot for the caption, `description` for supporting text, and `error` / `invalid` for validation. `class` styles the entire label; form attributes go to the input. Native radio grouping is controlled by a shared `name`. Unchecked inputs are omitted from native form submission; add your own hidden fallback when a backend requires a false value.

Checkbox `indeterminate` initializes the native mixed state with a small inline script. Clicking it resumes ordinary checked/unchecked behavior. A `nonce` prop is available for the script when required by your application's CSP. Other checkbox, radio and switch behavior requires no JavaScript.

## Badge

```blade
<x-badge color="success" dot>Published</x-badge>
<x-badge color="warning" variant="surface">Pending review</x-badge>
<x-badge href="/releases" color="contrast" variant="outline">v2.0</x-badge>
```

`dot` adds a small indicator. `leading` and `trailing` support named slots. The default element is `span`; `as` selects another tag and `href` renders a link through the shared primitive. Badges do not add dismissal behavior; place an explicit action in a trailing slot if needed.

## Progress

```blade
<x-progress :value="65" label="Uploading" show-value />
<x-progress :value="3" :max="8" variant="striped" color="success" />
<x-progress indeterminate aria-label="Loading results" />
```

Uses native `<progress>`. `value` is clamped between zero and a positive `max` (default 100). `show-value` displays a percentage. `indeterminate` omits the native value and shows a moving highlight, respecting reduced motion. `class` / `style` target the wrapper; `id`, `aria-*` and other native attributes target the progress element. The label is optional.

## Divider

```blade
<x-divider />
<x-divider align="start">Account settings</x-divider>
<x-divider variant="dashed" color="brand">Or continue with</x-divider>
<x-divider orientation="vertical" class="h-16" />
```

Default orientation is `horizontal`; text alignment is `start`, `center` (default) or `end`. The default slot accepts text or markup. A vertical divider needs a height or a stretching flex/grid parent.

## Slider

```blade
<x-slider name="volume" label="Volume" :value="40" show-value suffix="%" />
<x-slider name="temperature" label="Temperature" :min="-20" :max="40"
    :step="0.5" :value="18.5" show-value suffix="°C" />
<x-slider color="success" variant="soft" wire:model.live="level" aria-label="Level" />
```

A native range input supplies pointer, touch and keyboard behavior. `min`, `max`, `step` and `value` are normalized; `step="any"` permits arbitrary increments. It has one thumb. `show-value` displays a live output, with optional `suffix`.

A small inline script, included once per render, paints the selected track and updates the output on native input events and form reset. It preserves existing event/model bindings and also handles sliders inserted later. Pass `nonce` if required by CSP. Without the script the input remains operable; the painted fill and output remain at their initial values until the next server render. Programmatic value changes should dispatch the ordinary `input` event to refresh the paint.

See [composition components](composition.md) for avatar, typography, tabs, panel and dialog.
