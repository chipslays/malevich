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
        ]);
    }
}
