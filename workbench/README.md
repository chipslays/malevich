# Interface examples

Run `composer serve`, then open:

- `/examples` — project overview, tasks, progress and team activity.
- `/examples/projects` — search, filters, selection, project dialogs and CSV export.
- `/examples/settings` — profile, notifications and appearance, built with tabs and form controls.
- `/malevich` — the existing individual component playground.

These are desktop compositions with a 14px base, Google Inter and the shared kit palette. They intentionally have no mobile layout. Images in avatars fall back to initials while loading or on failure. The example portrait is loaded from Unsplash; without network access the fallback remains usable.

Icons are supplied by the dev dependency `mallardduck/blade-lucide-icons`:

```blade
<x-icon name="lucide-user" class="shrink-0 size-4" />
```

The workbench registers a thin static Blade wrapper around the package's `svg()` helper. This avoids generating temporary Blade files for every repeated SVG on Windows. The reusable kit itself does not require Lucide.

Project and settings changes persist in the local browser session. No database, account or external notification service is needed. Sample data is fictional. Theme selection is remembered in local storage. The examples keep native validation, form submission and keyboard behavior.

## Optional local stylesheet

The workbench can use its existing Tailwind browser CDN. For deterministic local CSS, build it with:

```bash
npm exec --yes --package=@tailwindcss/cli -- node workbench/build-examples.mjs
```

This creates ignored `build/examples.css`; the layout automatically serves that file at `/examples/assets/ui.css`. Run the command again after changing component classes or example templates. Google Inter still loads through Google Fonts, with a system font fallback.

The shared page layout is `resources/views/layouts/workspace.blade.php`. Page interactions live in `resources/views/partials/scripts.blade.php`; reusable behavior for avatar, tabs and slider stays inside its respective kit component. Controller actions live in `app/Http/Controllers/ExamplesController.php`.
