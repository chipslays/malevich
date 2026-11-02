<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class WorkbenchTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $compiled = dirname(__DIR__).'/build/workbench-test-views';
        (new Filesystem)->ensureDirectoryExists($compiled);
        $app['config']->set('view.compiled', $compiled);
    }

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), WorkbenchServiceProvider::class];
    }
}
