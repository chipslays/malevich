<?php

namespace Tests;

use Malevich\MalevichServiceProvider;
use Malevich\Support\DirectiveRegistry;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MalevichServiceProvider::class];
    }

    /**
     * The package config is merged automatically by the service provider,
     * so tests get the real default_target/directives/components values
     * unless a test explicitly overrides them with config([...]).
     */
    protected function defineEnvironment($app): void
    {
        //
    }

    /**
     * DirectiveRegistry keeps its own static state (registered presets, and
     * a WeakMap of directive configs) that lives outside of the Laravel
     * container and therefore survives between tests in the same process.
     * Presets in particular are a plain array and won't be garbage
     * collected on their own, so we reset both between every test to keep
     * tests independent of execution order.
     */
    protected function resetMalevichRegistry(): void
    {
        $ref = new ReflectionClass(DirectiveRegistry::class);

        $presets = $ref->getProperty('presets');
        $presets->setAccessible(true);
        $presets->setValue(null, []);

        $directives = $ref->getProperty('directives');
        $directives->setAccessible(true);
        $directives->setValue(null, null);
    }
}
