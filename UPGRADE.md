# Upgrade guide

## From 1.x to 2.0

Run `php artisan view:clear` after updating: compiled views contain calls that changed.

### "Directives" are now "options"

A prop that picks classes (`variant`, `size`, `color`, ...) is called an **option** everywhere: in the config, in the universal directive and in the `$attributes` API.

| 1.x | 2.0 |
|---|---|
| `'directives' => [...]` in `config/malevich.php` | `'options' => [...]` |
| `@directive('padding', [...])` | `@option('padding', [...])` |
| `$attributes->directive('padding', 'lg')` | `$attributes->option('padding', 'lg')` |
| `Malevich::directives()` | `Malevich::options()` |

If you published the config, rename the key. Search your views for `@directive(` and `->directive(`.

Tailwind IntelliSense: in `.vscode/settings.json` replace `directive` with `option` in the Malevich pattern (`@(?:base|variant|color|size|option|compound|cases)...`), or run `php artisan malevich:install --components=none`, which replaces the old entry.

### `Malevich` is a facade

`Malevich\Malevich` is now a facade for the `Malevich\MalevichManager` singleton. `Malevich::strict()`, `Malevich::mergeClassesUsing()` and the other calls work as before. If you used the internals:

| 1.x | 2.0 |
|---|---|
| `Malevich::SCOPE_VARIABLE`, `Malevich::UI_VARIABLE` | `MalevichManager::SCOPE_VARIABLE`, `MalevichManager::UI_VARIABLE` |
| `Malevich\Support\RecipeRegistry` | removed - use `Malevich::recipe($attributes)` |
| `Recipe::axis()`, `axesFor()`, `axisNames()`, `hasAxis()` | `Recipe::option()`, `optionsFor()`, `optionNames()`, `hasOption()` |
| `Element::directive()` | `Element::option()` |
| `new Recipe` | `new Recipe($defaultTarget = 'default')` |

### Behaviour changes

- **tailwind-merge is used automatically** when `gehrisandro/tailwind-merge-laravel` (or `-php`) is installed. Set `'merge_classes' => false` to keep de-duplication only. `Malevich::mergeClassesUsing()` still wins.
- **`<x-malevich::primitive>` refuses** `script`, `style`, `iframe`, `object`, `embed`, `link`, `meta`, `base`, `frame`, `frameset` and `applet` as `as`.
- **Classes are escaped**: a `"` in a class map or from a class merger becomes `&quot;` instead of breaking out of the attribute.
- **`@ui` outside a component** throws a `LogicException` that says so, instead of "Undefined variable $attributes".
- **`make:malevich`** writes a `@defaults([...])` block instead of `@props` for the options.

### New in 2.0

`@defaults`, `Malevich::strict()`, `php artisan malevich:install`, `php artisan malevich:lint`, data attributes, `classesFor()` for Alpine, `->assertSeeClasses()` / `toHaveClasses()`. See the README.
