<?php

declare(strict_types=1);

namespace Malevich\Exceptions;

use InvalidArgumentException;
use Malevich\Malevich;

/**
 * Thrown in strict mode when an axis gets a value its class maps don't
 * know, e.g. `size="xl"` on a component that only declares sm, md and lg.
 */
final class UnknownValueException extends InvalidArgumentException
{
    /**
     * @param  list<string>  $expected
     */
    public static function make(string $axis, string $value, string $target, array $expected): self
    {
        $element = $target === Malevich::defaultTarget() ? '' : " (element [{$target}])";
        $expected = $expected === [] ? 'no values are declared' : 'expected one of: '.implode(', ', $expected);

        return new self("Malevich: {$axis} [{$value}] is not defined{$element}; {$expected}.");
    }
}
