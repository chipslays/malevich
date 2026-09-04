<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\Support\Arr;
use Stringable;

/**
 * Accumulates one or more class fragments - each of which may be a plain
 * string, an array, or a conditional array as accepted by Laravel's
 * Arr::toCssClasses() - and compiles them into a single, de-duplicated
 * class string.
 *
 * Instances are immutable: add() returns a new instance rather than
 * mutating the current one, which lets a Selector resolve several
 * directives against the same starting builder without them interfering
 * with one another.
 */
final class ClassListBuilder implements Stringable
{
    /**
     * Compiled (but not yet split/de-duplicated) class fragments, in the
     * order they were added.
     *
     * @var list<string>
     */
    private array $compiled = [];

    /**
     * Compile the given classes and queue them for inclusion in the final
     * class string. Empty/falsy results are ignored.
     */
    public function add(mixed $classes): static
    {
        $compiled = Arr::toCssClasses($classes);

        if ($compiled === '') {
            return $this;
        }

        $clone = clone $this;
        $clone->compiled[] = $compiled;

        return $clone;
    }

    /**
     * Flatten every queued fragment into individual class names,
     * de-duplicate them (preserving first-seen order) and join them into
     * the final class string.
     */
    public function __toString(): string
    {
        return collect($this->compiled)
            ->flatMap(fn (string $classString) => explode(' ', $classString))
            ->filter()
            ->unique()
            ->implode(' ');
    }
}
