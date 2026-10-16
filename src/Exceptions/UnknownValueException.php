<?php

declare(strict_types=1);

namespace Malevich\Exceptions;

use InvalidArgumentException;

/**
 * Thrown in strict mode when an option gets a value its class maps don't
 * know, e.g. `size="xl"` on a component that only declares sm, md and lg.
 */
final class UnknownValueException extends InvalidArgumentException
{
    /**
     * @param  string|null  $element  The inner element (target), or null for the main one.
     * @param  list<string>  $expected
     */
    public static function make(string $option, string $value, ?string $element, array $expected): self
    {
        $element = $element === null ? '' : " (element [{$element}])";
        $expected = $expected === [] ? 'no values are declared' : 'expected one of: '.implode(', ', $expected);

        return new self("Malevich: {$option} [{$value}] is not defined{$element}; {$expected}.");
    }
}
