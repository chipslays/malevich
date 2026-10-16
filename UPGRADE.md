# Upgrade guide

## From 1.x to 2.0

Run `php artisan view:clear` after updating: compiled views contain calls that changed.

### `@ui` is now `@attributes`

The directive that prints an element's classes and attributes is called after what it prints:

| 1.x | 2.0 |
|---|---|
| `<button @ui>`, `<svg @ui('icon')>` | `<button @attributes>`, `<svg @attributes('icon')>` |
| `@ui(merge: [...])`, `@ui('title', slot: ...)` | `@attributes(merge: [...])`, `@attributes('title', slot: ...)` |
| `'render_directive' => 'ui'` (default) | `'directives' => ['attributes' => 'attributes', ...]` |
| `Malevich::ui(...)` | `Malevich::attributes(...)` |

Replace `@ui` in your components (a search for `@ui` followed by `>`, `(` or a space finds them all). To keep writing `@ui`, set `'directives' => ['attributes' => 'ui']` in your config.

### "Directives" are now "options"

A prop that picks classes (`variant`, `size`, `color`, ...) is called an **option** everywhere: in the config, in the universal directive and in the `$attributes` API.

| 1.x | 2.0 |
|---|---|
| `'directives' => [...]` in `config/malevich.php` | `'options' => [...]` |
| `@directive('padding', [...])` | `@option('padding', [...])` |
| `$attributes->directive('padding', 'lg')` | `$attributes->option('padding', 'lg')` |
| `Malevich::directives()` | `Malevich::options()` |

If you published the config, rename the key. Search your views for `@directive(` and `->directive(`.

Tailwind IntelliSense: in `.vscode/settings.json` replace `directive` with `option` in the Malevich pattern (`@(?:base|variant|color|size|option|compound|cases)...`), or run `php artisan malevich:install`, which replaces the old entry.

### `Malevich` is a facade

`Malevich\Malevich` is now a facade for the `Malevich\MalevichManager` singleton. `Malevich::strict()`, `Malevich::mergeClassesUsing()` and the other calls work as before. If you used the internals:

| 1.x | 2.0 |
|---|---|
| `Malevich::SCOPE_VARIABLE`, `Malevich::UI_VARIABLE` | `MalevichManager::SCOPE_VARIABLE`, `MalevichManager::ATTRIBUTES_VARIABLE` |
| `Malevich\Support\RecipeRegistry` | removed - use `Malevich::recipe($attributes)` |
| `Recipe::axis()`, `axesFor()`, `axisNames()`, `hasAxis()` | `Recipe::option()`, `optionsFor()`, `optionNames()`, `hasOption()` |
| `Element::directive()` | `Element::option()` |
| `new Recipe` | `new Recipe($defaultTarget = 'default')` |

### `@preset` is removed

`@preset(...)`, the `preset="..."` attribute and `$attributes->preset()` are gone. Give options their defaults in `@props` and pass the values you need on the tag. A combination you repeat a lot is better off as a component of its own:

```blade
{{-- 1.x --}}
@preset('featured', ['variant' => 'elevated', 'padding' => 'lg'])
<x-card preset="featured">

{{-- 2.0 --}}
<x-card variant="elevated" padding="lg">
```

### Directive names live in one place

`render_directive`, `cases_directive` and `has_directive` are replaced by one `directives` array, which now also covers `@base`, `@option` and `@compound`:

```php
'directives' => [
    'attributes' => 'attributes',
    'base' => 'base',
    'option' => 'option',
    'compound' => 'compound',
    'cases' => 'cases',
    'has' => 'has',
],
```

If you published the config, move your custom names there. `Malevich::renderDirective()`, `casesDirective()` and `hasDirective()` are now `Malevich::directive('attributes')`, `directive('cases')` and `directive('has')`.

### Behaviour changes

- **tailwind-merge is used automatically** when `gehrisandro/tailwind-merge-laravel` (or `-php`) is installed. Set `'merge_classes' => false` to keep de-duplication only. `Malevich::mergeClassesUsing()` still wins.
- **`<x-malevich::primitive>` refuses** `script`, `style`, `iframe`, `object`, `embed`, `link`, `meta`, `base`, `frame`, `frameset` and `applet` as `as`.
- **Classes are escaped**: a `"` in a class map or from a class merger becomes `&quot;` instead of breaking out of the attribute.
- **A `class="..."` next to `@attributes`** inside a component now works like `@base`: it comes before the option classes instead of after the `@compound` ones. It can also be combined with `merge:` now. If you relied on such a class overriding an option's classes, move it to `@attributes(merge: ['class' => ...])`.
- **`@attributes` outside a component** throws a `LogicException` that says so, instead of "Undefined variable $attributes".

### New in 2.0

`Malevich::strict()` / `MALEVICH_STRICT`, `Malevich::debug()` / `MALEVICH_DEBUG`, `php artisan malevich:install`, `php artisan malevich:lint`, data attributes (with `data-component`), `@compound(..., targets: [...])`, `classesFor()` for Alpine, `->assertSeeClasses()` / `toHaveClasses()`. See the README.
