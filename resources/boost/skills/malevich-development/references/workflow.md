# Workflow, conversion, verification

## Before writing a component

1. Read `config/malevich.php` (`directives`, `components.path`, `components.prefix`) and the existing components in that folder. Match naming, option and value names, palette, token style (`zinc` vs `gray`).
2. Check whether a similar component exists; extend it instead of duplicating.
3. Check the Tailwind major version (v3/v4 syntax differences) and match it.
4. Scaffold: `php artisan make:malevich name` (or by hand). Delete unused blocks.
5. Start from the closest file in `templates/` (button, badge, alert, card, switch).
6. Verify with the checklist below.

## `make:malevich`

```bash
php artisan make:malevich button          # <components.path>/button.blade.php
php artisan make:malevich forms/input     # forms/input.blade.php (forms.input also works)
php artisan make:malevich button --force  # overwrite
```

Scaffolds empty `@base`, a `@props` entry and an empty map for every directive in config (including custom). Never overwrites without `--force`. Tag depends on `components.prefix`:

| prefix | file | tag |
|---|---|---|
| `null` | `button.blade.php` | `<x-button>` |
| `null` | `forms/input.blade.php` | `<x-forms.input>` |
| `'ui'` | `ui/button.blade.php` | `<x-ui::button>` or `<x-ui.button>` |

Config defaults: `components.path` = `resource_path('views/components/ui')`, `prefix` = `null`, `directives` = `['variant','size','color']`, `render_directive` = `'ui'`, `default_target` = `'default'`. Publish with `php artisan vendor:publish --tag malevich:config`.

## Converting an existing component

Before:

```blade
@props(['variant' => 'primary', 'size' => 'md'])
<button {{ $attributes->class([
    'inline-flex items-center rounded-lg font-medium',
    'bg-black text-white' => $variant === 'primary',
    'border border-gray-300' => $variant === 'outline',
    'h-8 px-3 text-sm' => $size === 'sm',
    'h-10 px-4' => $size === 'md',
    'shadow-lg' => $variant === 'primary' && $size === 'lg',
]) }}>{{ $slot }}</button>
```

After:

```blade
@props(['variant' => 'primary', 'size' => 'md'])

@base('inline-flex items-center rounded-lg font-medium')
@variant(['primary' => 'bg-black text-white', 'outline' => 'border border-gray-300'])
@size(['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4'])
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')

<button @ui(merge: ['type' => 'button'])>{{ $slot }}</button>
```

Mapping: unconditional classes -> `@base`; classes conditioned on one variable -> that option's map; `&&` conditions -> `@compound`; `$attributes->merge([...])` defaults -> `@ui(merge: [...])`; classes of nested elements -> targets. Keep public props unchanged.

## Tailwind conflicts

Default: only duplicates are removed. To let the user's classes win, install `gehrisandro/tailwind-merge-laravel` and in `AppServiceProvider::boot()`:

```php
use Malevich\Malevich;
use TailwindMerge\Laravel\Facades\TailwindMerge;

Malevich::mergeClassesUsing(fn (string $classes) => TailwindMerge::merge($classes));
```

Any `fn (string): string` works.

## Editor support

Tailwind IntelliSense `.vscode/settings.json`:

```json
{
    "tailwindCSS.includeLanguages": { "blade": "html" },
    "tailwindCSS.experimental.classRegex": [
        [
            "@(?:base|variant|color|size|directive|compound)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)",
            "(?:^|=>|[\\[(,])\\s*[\"']([^\"']*)[\"'](?!\\s*=>)"
        ]
    ]
}
```

Add custom directive names to the first pattern.

## Verification checklist

- [ ] Every directive is above `@ui`.
- [ ] Every `@props` default exists as a key in its map (or is `null` with presets).
- [ ] No dynamic class-name concatenation.
- [ ] Each `@ui('x')` has matching `@base('x', ...)` / option maps if it needs styling.
- [ ] No leaked option attributes in the output (`variant="..."`).
- [ ] Accessibility defaults in `merge:`; `data-disabled:` used with the primitive.
- [ ] Usage comment at the top of the file.
- [ ] Rendered once to confirm output, e.g. Pest/PHPUnit: `$this->blade('<x-button variant="outline" size="sm">Go</x-button>')->assertSee('border border-gray-300', false)`, or open it in the app after `php artisan view:clear`.
- [ ] If tailwind-merge isn't installed and users will override `class`, mention the conflict caveat.

## Troubleshooting

- Classes missing: directive after `@ui`; value doesn't match a key; default is `null`; stale cache -> `php artisan view:clear`.
- `variant="..."` visible in HTML: directive missing or after `@ui`.
- `class` not on an inner element: by design; use a named slot or a prop.
- Preset ignored: option has a non-null `@props` default.
- Nested component got no classes: the child doesn't print `$attributes`.
- Works with Livewire/Alpine (`wire:*`, `x-*`, `@click` pass through) and without Tailwind (any CSS). Octane-safe.
- `@ui` outside components fails: it needs the component's `$attributes`.
