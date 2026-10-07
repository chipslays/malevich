<?php

namespace Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Malevich\Malevich;
use Malevich\MalevichServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MalevichServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Malevich::flush();
        Blade::anonymousComponentPath(__DIR__.'/fixtures/components');

        // Compiled fixture views are cached by file time, so a change in how a
        // directive compiles would otherwise be hidden by a stale cache.
        if (! self::$viewsCleared) {
            File::cleanDirectory((string) config('view.compiled'));
            self::$viewsCleared = true;
        }
    }

    private static bool $viewsCleared = false;

    /**
     * Render a Blade string and squash whitespace so assertions stay readable.
     */
    protected function render(string $template, array $data = []): string
    {
        return trim(preg_replace('/\s+/', ' ', Blade::render($template, $data, deleteCachedView: true)));
    }
}
