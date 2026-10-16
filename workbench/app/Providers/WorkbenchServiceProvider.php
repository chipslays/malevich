<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Settings for the playground only (`composer serve`), so the package config
 * keeps its documented defaults and the tests don't depend on them.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'malevich.data_attributes' => true,
            'malevich.debug' => true,
            'malevich.catalog.enabled' => true,
            // No Vite build here, so Tailwind comes from a CDN. In an app the catalog loads its own Vite entries.
            'malevich.catalog.head' => '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>',
        ]);
    }
}
