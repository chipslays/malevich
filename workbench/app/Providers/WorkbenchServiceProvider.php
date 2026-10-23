<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * The playground (`composer serve`): the ready-made components from
 * resources/kit in the component catalog. Its settings live here, so the
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
            'malevich.catalog.enabled' => true,
            // No Vite build here, so Tailwind comes from a CDN. In an app the catalog loads its own Vite entries.
            // Class-based dark mode, so the catalog's dark / light buttons switch the components too.
            'malevich.catalog.head' => '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>'
                .'<style type="text/tailwindcss">@custom-variant dark (&:where(.dark, .dark *));'.self::theme().'</style>',
        ]);
    }

    /**
     * The theme tokens the kit expects from an app: semantic color scales and
     * the `brand` radius, mapped onto Tailwind's built-in palette.
     */
    private static function theme(): string
    {
        $scales = [
            'primary' => 'indigo',
            'secondary' => 'slate',
            'accent' => 'fuchsia',
            'success' => 'emerald',
            'warning' => 'amber',
            'danger' => 'red',
            'info' => 'sky',
        ];

        $tokens = ['--radius-brand: var(--radius-lg);'];

        foreach ($scales as $name => $color) {
            foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $shade) {
                $tokens[] = "--color-{$name}-{$shade}: var(--color-{$color}-{$shade});";
            }
        }

        return '@theme inline {'.implode('', $tokens).'}';
    }

    public function boot(): void
    {
        // The kit as <x-button>, <x-badge>, ... - edit resources/kit/*.blade.php and refresh.
        Blade::anonymousComponentPath(__DIR__.'/../../../resources/kit');
    }
}
