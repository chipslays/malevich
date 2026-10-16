# Tailwind

Malevich works with any CSS, but most people use it with Tailwind. Two things make that nicer: resolving class conflicts, and autocomplete inside the directives.

## Class conflicts

The button has `px-4`, and you write `<x-button class="px-8">`. By default both end up in the HTML, and which one wins depends on the order of Tailwind's CSS - not on what you meant.

Install [tailwind-merge](https://github.com/gehrisandro/tailwind-merge-laravel), and the class you pass always wins:

```bash
composer require gehrisandro/tailwind-merge-laravel
```

```blade
<x-button class="px-8">Save</x-button>   {{-- the component's px-4 is gone, px-8 stays --}}
```

There's nothing to configure - Malevich finds it by itself. The plain PHP package (`gehrisandro/tailwind-merge-php`) works too.

- Want only de-duplication even with tailwind-merge installed? Set `'merge_classes' => false` in the [config](13-configuration.md).
- Want your own logic? Give Malevich any function from the class string to a new one. It always wins:

  ```php
  // app/Providers/AppServiceProvider.php
  use Malevich\Malevich;

  public function boot(): void
  {
      Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes));
  }
  ```

> [!TIP]
> Not sure what tailwind-merge removed? [Debug mode](10-debugging.md#debug-mode) shows it in an HTML comment next to the element.

## Autocomplete in your editor

The [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss) extension only looks for classes in `class="..."`. To get autocomplete, hover previews and color swatches inside `@base`, `@variant` and the rest, tell it where to look.

The easy way:

```bash
php artisan malevich:install
```

It writes the settings below into `.vscode/settings.json`, including your own options and directive names, and keeps your other settings.

By hand:

```json
{
    "tailwindCSS.includeLanguages": {
        "blade": "html"
    },
    "tailwindCSS.experimental.classRegex": [
        [
            "@(?:base|variant|color|size|option|compound|cases)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)",
            "(?:^|=>|[\\[(,])\\s*[\"']([^\"']*)[\"'](?!\\s*=>)"
        ]
    ]
}
```

- The first pattern finds a directive, the second picks the strings with classes inside it - the values, not the `'primary' =>` keys.
- Added [your own options](13-configuration.md#your-own-options)? Add them to the first pattern: `(?:base|variant|...|radius)`.
- Reload the window after changing settings (**Developer: Reload Window**).
- The same `classRegex` works in other editors with the Tailwind language server: PhpStorm, Zed, Neovim.

## Tailwind's own `@variant`

Tailwind has a CSS at-rule called `@variant`. If you write CSS in a `<style>` tag inside a Blade file, `@variant dark (...)` is left alone: Malevich only reacts to `@variant([...])` with parentheses right after the name.

---

Next: [JavaScript and CSS hooks](08-javascript.md)
