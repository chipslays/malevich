<?php

declare(strict_types=1);

namespace Malevich\Concerns;

use Illuminate\Support\Facades\Blade;

/**
 * Registers the package's Blade views/anonymous components and exposes the
 * hook points a consuming application uses to publish and override them.
 */
trait RegistersViews
{
    protected function registerViews(): void
    {
        $this->loadViewsFrom($this->viewsPath(), 'malevich');

        $prefix = config('malevich.components.prefix', 'ui');

        // The application's own published components (if any) are
        // registered first: Blade resolves anonymous component paths in
        // registration order and stops at the first match, so an app-level
        // override always takes precedence over the package's default.
        Blade::anonymousComponentPath(config('malevich.components.path', resource_path('views/components/ui')), $prefix);
        Blade::anonymousComponentPath($this->viewsPath().'/components/ui', $prefix);
    }

    protected function viewsPath(): string
    {
        return __DIR__.'/../../resources/views';
    }
}
