# Artisan commands

| Command | What it does |
|---|---|
| `php artisan malevich:install` | Sets up the project: config, editor autocomplete. |
| `php artisan malevich:add button` | Copies a ready-made kit component into the project. |
| `php artisan make:malevich button` | Creates a component file. |
| `php artisan malevich:lint` | Finds unknown option values in all views. |

## `malevich:install`

```bash
php artisan malevich:install
```

Optional, but sets everything up in one go:

- publishes `config/malevich.php`;
- writes [Tailwind autocomplete](07-tailwind.md#autocomplete-in-your-editor) settings into `.vscode/settings.json` (your other settings are kept);
- tells you whether [Tailwind conflicts](07-tailwind.md#class-conflicts) are handled.

It creates no components - those are yours.

```bash
php artisan malevich:install --no-vscode   # leave .vscode/settings.json alone
php artisan malevich:install --force       # overwrite an existing config/malevich.php
```

Prefer to do it by hand? Publish the config:

```bash
php artisan vendor:publish --tag malevich:config
```

## `malevich:add`

```bash
php artisan malevich:add button
php artisan malevich:add button --force    # replace an existing copy
php artisan malevich:add                   # pick a kit component from a list
```

Copies the complete component from the package's [kit](../resources/kit/README.md) into `malevich.components.path`, creating the folder if needed. The default destination is `resources/views/components/button.blade.php`. The command prints the tag to use, including your configured `components.prefix`.

The component is now your source code: customize it directly. Package updates leave your copy alone. Existing files are never overwritten without `--force`; an unknown name lists available kit components. The `.blade.php` suffix is optional.

Kit components use semantic Tailwind colors (`primary`, `danger`, ...) and a `brand` radius. Publish the default tokens once and import them after Tailwind; the command reminds you until `resources/css/malevich-theme.css` exists:

```bash
php artisan vendor:publish --tag=malevich:theme
```

See [theme tokens](../resources/kit/README.md#theme-tokens) for the import and for defining the tokens yourself.

This differs from `make:malevich`, which creates an empty scaffold, and `vendor:publish --tag=malevich:components`, which publishes the internal unstyled primitive.

## `make:malevich`

```bash
php artisan make:malevich button            # resources/views/components/button.blade.php
php artisan make:malevich forms/input       # resources/views/components/forms/input.blade.php
php artisan make:malevich button --force    # overwrite an existing file
```

The new file has everything to fill in: a usage example on top (the [playground](09-playground.md) uses it), a `@props` entry and an empty class map for every option in your config, and an empty `@base`. Delete what you don't need:

```blade
{{--
    <x-button>Button</x-button>
--}}

@props([
    'variant' => null,
    'size' => null,
    'color' => null,
])

@base('')

@variant([])

@size([])

@color([])

<div @attributes>
    {{ $slot }}
</div>
```

- It never overwrites an existing file without `--force`.
- `forms/input`, `forms.input` and `forms/input.blade.php` are the same.
- The folder is `components.path` in the [config](13-configuration.md). The command prints the tag to use.

### Which tag?

It depends on `components.prefix`:

| `prefix` | File | Tag |
|---|---|---|
| `null` (default) | `button.blade.php` | `<x-button>` |
| `null` | `forms/input.blade.php` | `<x-forms.input>` |
| `'ui'` | `button.blade.php` | `<x-ui::button>` |
| `'ui'` | `forms/input.blade.php` | `<x-ui::forms.input>` |

A prefix *adds* a tag. While the folder is `resources/views/components`, Laravel still serves `<x-button>` too. For prefixed tags only, give the components their own folder:

```php
// config/malevich.php
'components' => [
    'path' => resource_path('views/ui'),
    'prefix' => 'ui',
],
```

Now `resources/views/ui/button.blade.php` is `<x-ui::button>` and nothing else.

## `malevich:lint`

```bash
php artisan malevich:lint
php artisan malevich:lint resources/views/admin   # only some folders or files
php artisan malevich:lint --format=github         # annotations on GitHub pull requests
```

Checks every `<x-...>` tag in your views against the values your components accept. See [Testing and CI](11-testing.md#catching-typos-before-the-page-is-rendered).

## Publishing the package components

```bash
php artisan vendor:publish --tag malevich:components
```

Copies the [unstyled primitive](06-building-components.md#the-unstyled-primitive) into your project so you can change it.

---

Next: [Configuration](13-configuration.md)
