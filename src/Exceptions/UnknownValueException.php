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

        return new self("Malevich: {$option} [{$value}] is not defined{$element}; {$expected}.".self::hint($option));
    }

    /**
     * An option named like an HTML attribute takes that attribute over:
     * `<x-input size="20">` never reaches the `<input>`. Say so.
     */
    private static function hint(string $option): string
    {
        $native = ['size', 'type', 'width', 'height', 'span', 'rows', 'cols', 'min', 'max', 'step', 'value', 'name', 'form', 'dir', 'lang', 'wrap', 'shape', 'align'];

        return in_array($option, $native, true)
            ? " [{$option}] is also an HTML attribute, but here it is an option of the component and is not printed. Give the option another name to pass {$option}=\"...\" to the element."
            : '';
    }
}
