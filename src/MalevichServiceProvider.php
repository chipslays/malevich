<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Support\ServiceProvider;
use Malevich\Concerns\RegistersComponentMacros;
use Malevich\Concerns\RegistersDynamicDirectives;
use Malevich\Concerns\RegistersViews;
use Malevich\Console\Commands\MakeCommand;

/**
 * Bootstraps Malevich: a small toolkit for declaring per-component class
 * variants (@variant, @color, @size, and any custom directive) directly
 * inside Blade templates, and resolving them fluently at render time via
 * `$attributes->for()->use()` or the matching per-directive macros.
 */
class MalevichServiceProvider extends ServiceProvider
{
    use RegistersComponentMacros;
    use RegistersDynamicDirectives;
    use RegistersViews;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/malevich.php', 'malevich');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->commands([
                MakeCommand::class,
            ]);
        }

        $this->registerComponentMacros();
        $this->registerDirectiveBladeDirectives();
        $this->registerDynamicDirectives();
        $this->registerViews();
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/malevich.php' => config_path('malevich.php'),
        ], 'malevich:config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views'),
        ], 'malevich:components');
    }
}
