<?php

namespace TailwindMerge;

/**
 * Stand-in for gehrisandro/tailwind-merge-php. Does nothing until a test
 * sets a prefix, so loading it can't change the output of other tests.
 */
final class TailwindMerge
{
    public static string $prefix = '';

    public static function instance(): self
    {
        return new self;
    }

    public function merge(string $classes): string
    {
        return self::$prefix === '' ? $classes : self::$prefix.$classes;
    }
}
