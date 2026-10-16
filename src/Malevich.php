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
 * @method static Recipe recipe(?ComponentAttributeBag $attributes)
 * @method static Element attributes(?ComponentAttributeBag $attributes, array<string, mixed> $scope, ?string $target = null, mixed $slot = null, array<string, mixed> $merge = [])
 * @method static bool has(?ComponentAttributeBag $attributes, array<string, mixed> $scope, ?string $target = null, mixed $slot = null, array<string, mixed> $merge = [])
 * @method static string defaultTarget()
 * @method static string renderDirective()
 * @method static string casesDirective()
 * @method static string hasDirective()
 * @method static list<string> options()
 * @method static bool dataAttributes()
 * @method static string componentTag(string $name)
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
