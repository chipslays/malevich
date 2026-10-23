<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ComponentAttributeBag;

/**
 * @method static void mergeClassesUsing(?Closure $merger)
 * @method static string mergeClasses(string $classes)
 * @method static void strict(bool $strict = true)
 * @method static bool isStrict()
 * @method static void debug(bool $debug = true)
 * @method static bool isDebugging()
 * @method static Recipe recipe(?ComponentAttributeBag $attributes)
 * @method static Element attributes(?ComponentAttributeBag $attributes, array<string, mixed> $scope, ?string $target = null, mixed $slot = null, array<string, mixed> $merge = [], string $class = '')
 * @method static bool styled(?ComponentAttributeBag $attributes, array<string, mixed> $scope, ?string $target = null, mixed $slot = null, array<string, mixed> $merge = [])
 * @method static string defaultTarget()
 * @method static string directive(string $role)
 * @method static array<string, string> directives()
 * @method static list<string> options()
 * @method static bool dataAttributes()
 * @method static string componentTag(string $name)
 * @method static string|null componentName(array<string, mixed> $scope)
 * @method static void flushConfig()
 * @method static void flush()
 *
 * @see MalevichManager
 */
class Malevich extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MalevichManager::class;
    }
}
