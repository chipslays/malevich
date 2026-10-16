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

Scaffolds empty `@base`, an empty map for every directive in config (including custom) and a `@defaults` block with a commented-out line per directive (no `@props`). Never overwrites without `--force`. Tag depends on `components.prefix`:

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
@base('inline-flex items-center rounded-lg font-medium')
@variant(['primary' => 'bg-black text-white', 'outline' => 'border border-gray-300'])
@size(['sm' => 'h-8 px-3 text-sm', 'md' => 'h-10 px-4'])
@compound(['variant' => 'primary', 'size' => 'lg'], 'shadow-lg')
@defaults(['variant' => 'primary', 'size' => 'md'])

<button @ui(merge: ['type' => 'button'])>{{ $slot }}</button>
```

Mapping: unconditional classes -> `@base`; classes conditioned on one variable -> that option's map; `&&` conditions -> `@compound`; `$attributes->merge([...])` defaults -> `@ui(merge: [...])`; classes of nested elements -> targets; `@props` defaults of options -> `@defaults` (drop them from `@props` unless the markup uses the variable). Keep public prop names and values unchanged.

## Tailwind conflicts

Without tailwind-merge only duplicates are removed. Installing `gehrisandro/tailwind-merge-laravel` (or `-php`) is enough: with `'merge_classes' => 'auto'` (the default) Malevich uses it by itself and the user's `class="px-8"` beats the component's `px-4`. `'merge_classes' => false` turns it off. A custom merger always wins:

```php
Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes)); // any fn (string): string
```

## Data attributes

`'data_attributes' => true` in `config/malevich.php` writes the resolved options on the main element: `data-variant="danger" data-size="lg"`, a true flag as `data-loading`, a false one left out. Use it for `group-data-[variant=danger]:` styling of children and for test/Dusk selectors instead of extra props.

## Testing components

Class assertions are registered automatically:

```php
$this->blade('<x-button variant="danger">Delete</x-button>')
    ->assertSeeClasses('bg-red-600 text-white')     // one element with all of them, any order
    ->assertDontSeeClasses('bg-zinc-900');
$this->get('/settings')->assertSeeClasses('rounded-2xl');
expect($html)->toHaveClasses(['bg-red-600', 'text-white']); // Pest
```

Prefer them over `assertSee('class="..."')`, which breaks on class order. Enable `Malevich::strict()` in tests to catch unknown values.

## Editor support

Tailwind IntelliSense `.vscode/settings.json`:

```json
{
    "tailwindCSS.includeLanguages": { "blade": "html" },
    "tailwindCSS.experimental.classRegex": [
        [
            "@(?:base|variant|color|size|directive|compound|cases)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)",
            "(?:^|=>|[\\[(,])\\s*[\"']([^\"']*)[\"'](?!\\s*=>)"
        ]
    ]
}
```

Add custom directive names to the first pattern.

## Verification checklist

- [ ] Every directive is above `@ui`.
- [ ] Option defaults are in `@defaults` and exist as keys in their maps; options in `@props` default to `null`.
- [ ] No dynamic class-name concatenation.
- [ ] Each `@ui('x')` has matching `@base('x', ...)` / option maps if it needs styling.
- [ ] No leaked option attributes in the output (`variant="..."`).
- [ ] Accessibility defaults in `merge:`; `data-disabled:` used with the primitive.
- [ ] Usage comment at the top of the file.
- [ ] Rendered once to confirm output, e.g. Pest/PHPUnit: `$this->blade('<x-button variant="outline" size="sm">Go</x-button>')->assertSee('border border-gray-300', false)`, or open it in the app after `php artisan view:clear`.
- [ ] If tailwind-merge isn't installed and users will override `class`, mention the conflict caveat.

## Troubleshooting

- Classes missing: directive after `@ui`; value doesn't match a key (`Malevich::strict()` names it); no default (add `@defaults`); stale cache -> `php artisan view:clear`.
- `variant="..."` visible in HTML: directive missing or after `@ui`.
- `class` not on an inner element: by design; use a named slot or a prop.
- Preset ignored: option has a non-null `@props` default. Move it to `@defaults`.
- Nested component got no classes: the child doesn't print `$attributes`.
- Works with Livewire/Alpine (`wire:*`, `x-*`, `@click` pass through) and without Tailwind (any CSS). Octane-safe.
- `@ui` outside components fails: it needs the component's `$attributes`.
