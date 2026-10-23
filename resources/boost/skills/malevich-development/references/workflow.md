# Workflow, conversion, verification

## Before writing a component

1. Read `config/malevich.php` (`options`, `components.path`, `components.prefix`) and the existing components in that folder. Match naming, option and value names, palette, token style (`zinc` vs `gray`).
2. Check whether a similar component exists; extend it instead of duplicating.
3. Check the Tailwind major version (v3/v4 syntax differences) and match it.
4. Scaffold: `php artisan make:malevich name` (or by hand). Delete unused blocks.
5. Start from the closest existing component in the project, or scaffold a new one with `php artisan make:malevich name`.
6. Verify with the checklist below.

## `make:malevich`

```bash
php artisan make:malevich button          # <components.path>/button.blade.php
php artisan make:malevich forms/input     # forms/input.blade.php (forms.input also works)
php artisan make:malevich button --force  # overwrite
```

Scaffolds a `@props` entry (default `null`) and an empty map for every option in config (including custom), plus an empty `@base`. Never overwrites without `--force`. Tag depends on `components.prefix`:

| prefix | file in `components.path` | tag |
|---|---|---|
| `null` | `button.blade.php` | `<x-button>` |
| `null` | `forms/input.blade.php` | `<x-forms.input>` |
| `'ui'` | `button.blade.php` | `<x-ui::button>` |

A prefix adds a tag, it doesn't remove the plain one: while `components.path` is `resources/views/components`, Laravel still serves the file as `<x-button>` too. For prefix-only tags use a folder of their own, e.g. `'path' => resource_path('views/ui'), 'prefix' => 'ui'`.

Config defaults: `options` = `['variant','size','color']`, `directives` = `attributes`, `base`, `option`, `compound`, `cases`, `styled` (each renamable), `components.path` = `resource_path('views/components')`, `components.prefix` = `null`, `default_target` = `'default'`, `data_attributes` = `false`, `merge_classes` = `'auto'`. Publish with `php artisan vendor:publish --tag malevich:config`.

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

<button @attributes(merge: ['type' => 'button'])>{{ $slot }}</button>
```

Mapping: unconditional classes -> `@base`; classes conditioned on one variable -> that option's map; `&&` conditions -> `@compound`; `$attributes->merge([...])` defaults -> `@attributes(merge: [...])`; classes of nested elements -> targets; `@props` defaults stay in `@props`. Keep public prop names and values unchanged.

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
            "@(?:base|variant|color|size|option|compound|cases)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)",
            "(?:^|=>|[\\[(,])\\s*[\"']([^\"']*)[\"'](?!\\s*=>)"
        ]
    ]
}
```

Add custom option names to the first pattern.

## Verification checklist

- [ ] Every directive is above `@attributes`.
- [ ] Option defaults in `@props` exist as keys in their maps.
- [ ] No dynamic class-name concatenation.
- [ ] Each `@attributes('x')` has matching `@base('x', ...)` / option maps if it needs styling.
- [ ] No leaked option attributes in the output (`variant="..."`).
- [ ] Accessibility defaults in `merge:`; `data-disabled:` used with the primitive.
- [ ] Usage comment at the top of the file.
- [ ] Rendered once to confirm output, e.g. `$this->blade('<x-button variant="outline" size="sm">Go</x-button>')->assertSeeClasses('border border-gray-300')`, or open it in the app after `php artisan view:clear`.
- [ ] `php artisan malevich:lint` passes after adding, renaming or removing option values.
- [ ] If tailwind-merge isn't installed and users will override `class`, mention the conflict caveat.

## Troubleshooting

- Turn on `MALEVICH_DEBUG=true`: an HTML comment in front of every element lists each option's value, its source and the classes every declaration added.
- Classes missing: directive after `@attributes`; value doesn't match a key (`Malevich::strict()` names it); no default (add one in `@props`); stale cache -> `php artisan view:clear`.
- `variant="..."` visible in HTML: directive missing or after `@attributes`.
- `class` not on an inner element: by design; use a named slot or a prop.
- Nested component got no classes: the child doesn't print `$attributes`.
- Works with Livewire/Alpine (`wire:*`, `x-*`, `@click` pass through) and without Tailwind (any CSS). Octane-safe.
- `@attributes` outside components fails: it needs the component's `$attributes`.
