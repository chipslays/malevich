# Configuration

Malevich works without any configuration. Publish the config when you want to change something:

```bash
php artisan vendor:publish --tag malevich:config
```

> [!NOTE]
> Blade compiles views once and caches them. After changing `config/malevich.php`, run `php artisan view:clear`.

## All settings

| Key | Default | What it does |
|---|---|---|
| `options` | `['variant', 'size', 'color']` | Options with a directive of their own: `@variant`, `@size`, ... [Add yours](#your-own-options). |
| `directives` | `attributes`, `base`, `option`, `compound`, `cases`, `styled` | The name of every Malevich directive. [Rename](#renaming-directives) on a clash. |
| `components.path` | `resource_path('views/components')` | Where `make:malevich` creates components and `malevich:add` copies kit components. |
| `components.prefix` | `null` | Tag prefix: `null` -> `<x-button>`, `'ui'` -> `<x-ui::button>`. See [Which tag?](12-commands.md#which-tag) |
| `default_target` | `'default'` | The name of the main element in per-element arrays and `@cases`. |
| `data_attributes` | `false` | Put `data-component`, `data-variant`, ... on the main element. See [Data attributes](08-javascript.md#data-attributes). |
| `merge_classes` | `'auto'` | `'auto'`: use tailwind-merge when it's installed. `false`: only remove duplicates. See [Class conflicts](07-tailwind.md#class-conflicts). |
| `strict` | `env('MALEVICH_STRICT', false)` | Throw on unknown option values. See [Strict mode](10-debugging.md#strict-mode). |
| `debug` | `env('MALEVICH_DEBUG', false)` | An HTML comment explaining every element. See [Debug mode](10-debugging.md#debug-mode). |
| `catalog.enabled` | `env('MALEVICH_CATALOG')` | The [component catalog](09-catalog.md). Empty: only in the `local` environment. |
| `catalog.path` | `'malevich'` | The catalog's URL. |
| `catalog.middleware` | `['web']` | Middleware for the catalog, e.g. `['web', 'auth']`. |
| `catalog.head` | `null` | HTML for the catalog's `<head>`. Empty: your app's Vite entries. |

## Your own options

`@option('radius', [...])` works in any component without configuration. If an option shows up in many components, give it a short directive of its own:

```php
// config/malevich.php
'options' => ['variant', 'size', 'color', 'radius'],
```

Now `@radius` works exactly like `@variant`:

```blade
@props(['radius' => 'md'])

@radius([
    'md' => 'rounded-md',
    'full' => 'rounded-full',
])
```

You also get `$attributes->radius('full')` in the [`$attributes` API](14-attributes-api.md), and new components from `make:malevich` get a `@radius` block.

Malevich checks every name on boot and throws a clear error when a name:

- is a built-in Blade directive (`if`, `class`, `props`, ...);
- is already a directive of your app or another package;
- is already one of Malevich's own directive names;
- is already a method of `$attributes` (`merge`, `get`, `only`, ...);
- has anything but letters, digits and `_`.

## Renaming directives

Every directive name can be changed, so a clash with another package is always solvable:

```php
// config/malevich.php
'directives' => [
    'attributes' => 'attributes',   // <button @attributes>
    'base' => 'base',               // @base('...')
    'option' => 'option',           // @option('radius', [...])
    'compound' => 'compound',       // @compound([...], '...')
    'cases' => 'cases',             // @cases('variant', [...])
    'styled' => 'styled',           // @styled('glow') ... @endstyled
],
```

- `@styled` is closed with `@end` + its name: rename it to `present` and you write `@present(...) ... @endpresent`.
- The editor autocomplete written by `malevich:install` follows the new names.
- Run `php artisan view:clear` after renaming.

---

Next: [The `$attributes` API](14-attributes-api.md)
