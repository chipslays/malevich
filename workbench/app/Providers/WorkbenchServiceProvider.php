<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * The playground (`composer serve`): the ready-made components from
 * resources/kit in the component playground. Its settings live here, so the
 * package config keeps its documented defaults and the tests don't depend
 * on them.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'malevich.data_attributes' => true,
            'malevich.debug' => true,
            'malevich.playground.enabled' => true,
            // No Vite build here, so Tailwind comes from a CDN. In an app the playground loads its own Vite entries.
            // Class-based dark mode, so the playground's dark / light buttons switch the components too.
            'malevich.playground.head' => '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>'
                // The same theme tokens an app gets from `vendor:publish --tag=malevich:theme`.
                .'<style type="text/tailwindcss">@custom-variant dark (&:where(.dark, .dark *));'
                .file_get_contents(__DIR__.'/../../../resources/kit/theme.css').'</style>',
        ]);
    }

    public function boot(): void
    {
        // The kit as <x-button>, <x-badge>, ... - edit resources/kit/*.blade.php and refresh.
        Blade::anonymousComponentPath(__DIR__.'/../../../resources/kit');
    }
}
