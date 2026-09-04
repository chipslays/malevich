<?php

namespace Malevich;

use Illuminate\View\ComponentAttributeBag;
use WeakMap;

class Registry
{
    /**
     * @var WeakMap<ComponentAttributeBag, array<string, array<string, mixed>>>
     */
    protected static ?WeakMap $map = null;

    protected static array $presets = [];

    protected static function init(): void
    {
        if (static::$map === null) {
            static::$map = new WeakMap;
        }
    }

    public static function registerDirective(ComponentAttributeBag $attributes, string $directive, mixed ...$args): void
    {
        static::init();

        $target = config('malevich.default_target', 'default');
        $config = $args[0];

        if (count($args) > 1) {
            $target = $args[0];
            $config = $args[1];
        }

        if (! isset(static::$map[$attributes])) {
            static::$map[$attributes] = [];
        }

        if (! isset(static::$map[$attributes][$target])) {
            static::$map[$attributes][$target] = [];
        }

        static::$map[$attributes][$target][$directive] = $config;
    }

    public static function getDirective(ComponentAttributeBag $attributes, string $target): array
    {
        static::init();

        return static::$map[$attributes][$target] ?? [];
    }

    public static function registerPreset(string $name, array $config): void
    {
        static::$presets[$name] = $config;
    }

    public static function getPreset(string $name): array
    {
        return static::$presets[$name] ?? [];
    }
}
