<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Malevich\Support\ClassListBuilder;
use Malevich\Support\DirectiveRegistry;
use Stringable;

/**
 * Fluent, immutable resolver for a component "target" — a named, separately
 * styled part of a component (e.g. a slider's "track" and "handle") — that
 * turns the directives declared against a component's attribute bag
 * (@variant, @color, @size, ...) into a final class string.
 *
 * A Selector is normally created through the `for()` / `use()` macros that
 * MalevichServiceProvider adds to ComponentAttributeBag, e.g.:
 *
 *     {{ $attributes->for('track')->use(['size' => $size]) }}
 *
 * Every mutator returns a new instance, so a selector can be safely reused
 * as a starting point for several sub-parts of a compound component.
 */
final class Selector implements Htmlable, Stringable
{
    /**
     * Attribute bag to merge the resolved classes onto when this selector
     * represents a slot rather than the component's own root element.
     */
    private ?ComponentAttributeBag $slotAttributes = null;

    /**
     * @param  ComponentAttributeBag  $attributes  The bag the directives were originally declared against.
     * @param  string|null  $target  The target this selector currently resolves classes for.
     * @param  array<string, mixed>  $choices  Directive => selected choice key, used to pick a class from each directive's map.
     */
    public function __construct(
        private readonly ComponentAttributeBag $attributes,
        private ?string $target = null,
        private array $choices = [],
    ) {
        $this->target ??= config('malevich.default_target', 'default');
    }

    /**
     * Switch which target this selector resolves classes for.
     */
    public function for(string $target): static
    {
        $clone = clone $this;
        $clone->target = $target;

        return $clone;
    }

    /**
     * Merge the resolved classes onto a slot's own attribute bag instead of
     * the component's root attribute bag. Useful when a directive needs to
     * style markup rendered from a slot.
     *
     * @param  ComponentAttributeBag|ComponentSlot|mixed  $slot
     */
    public function slot(mixed $slot): static
    {
        $clone = clone $this;
        $clone->slotAttributes = static::extractAttributeBag($slot);

        return $clone;
    }

    /**
     * Set one or more directive choices at once, or switch target and set
     * choices in a single call.
     *
     * Array form — each value may either be a plain choice, or an array
     * keyed by target from which the choice for the *current* target is
     * picked:
     *
     *     ->use(['size' => ['track' => 'lg', 'handle' => 'sm']])
     *
     * String form — shorthand for `for($target)->use($choices)`:
     *
     *     ->use('track', ['size' => 'lg'])
     *
     * @param  array<string, mixed>|string  $choicesOrTarget
     * @param  array<string, mixed>  $choices
     */
    public function use(array|string $choicesOrTarget = [], array $choices = []): static
    {
        $clone = clone $this;

        if (is_string($choicesOrTarget)) {
            $clone->target = $choicesOrTarget;
            $clone->choices = array_merge($clone->choices, $choices);

            return $clone;
        }

        foreach ($choicesOrTarget as $directive => $value) {
            $clone->choices[$directive] = is_array($value)
                ? ($value[$clone->target] ?? null)
                : $value;
        }

        if ($choices !== []) {
            $clone->choices = array_merge($clone->choices, $choices);
        }

        return $clone;
    }

    /**
     * Set the selected choice for a single directive.
     *
     * If $value is an array, it is treated as a per-target map and the
     * choice for the current target is picked from it.
     */
    public function directive(string $directive, mixed $value): static
    {
        $clone = clone $this;
        $clone->choices[$directive] = is_array($value)
            ? ($value[$clone->target] ?? null)
            : $value;

        return $clone;
    }

    /**
     * Apply every directive value from a named preset (registered via
     * @preset) to the current target.
     */
    public function preset(string $name): static
    {
        $preset = DirectiveRegistry::getPreset($name);
        $targetPreset = $preset[$this->target] ?? $preset;

        $clone = clone $this;

        foreach ($targetPreset as $directive => $value) {
            $clone = $clone->directive($directive, $value);
        }

        return $clone;
    }

    /**
     * Allow any directive to be set fluently by name, e.g. `->color('red')`
     * as an alternative to `->directive('color', 'red')`.
     */
    public function __call(string $name, array $arguments): static
    {
        if (! isset($arguments[0])) {
            return $this;
        }

        return $this->directive($name, $arguments[0]);
    }

    /**
     * Resolve the final, de-duplicated class string for the current target
     * by walking every directive registered against it, applying any "*"
     * wildcard (always-applied) fragment plus the fragment for the
     * selected choice, then appending the `class` attribute from the
     * relevant attribute bag so consumers can still extend/override
     * classes from the outside.
     */
    public function resolveClasses(): string
    {
        $builder = new ClassListBuilder;

        foreach (DirectiveRegistry::getDirectives($this->attributes, $this->target) as $directive => $map) {
            if (isset($map['*'])) {
                $builder = $builder->add($map['*']);
            }

            $choice = $this->choices[$directive] ?? null;

            if ($choice && isset($map[$choice])) {
                $builder = $builder->add($map[$choice]);
            }
        }

        if (($ownAttributes = $this->ownAttributes()) && $ownAttributes->has('class')) {
            $builder = $builder->add($ownAttributes->get('class'));
        }

        return (string) $builder;
    }

    public function toHtml(): string
    {
        $classes = $this->resolveClasses();

        if ($ownAttributes = $this->ownAttributes()) {
            return $ownAttributes
                ->except(['class', 'as'])
                ->merge(['class' => $classes])
                ->toHtml();
        }

        return (new ComponentAttributeBag(['class' => $classes]))->toHtml();
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * The attribute bag whose non-class attributes should be rendered
     * alongside the resolved classes: the slot's bag when one was set via
     * slot(), otherwise the component's own bag — but only while resolving
     * the default target, since non-default targets represent a sub-part
     * of the component rather than its root element.
     */
    private function ownAttributes(): ?ComponentAttributeBag
    {
        return $this->slotAttributes ?? ($this->isDefaultTarget() ? $this->attributes : null);
    }

    private function isDefaultTarget(): bool
    {
        return $this->target === config('malevich.default_target', 'default');
    }

    private static function extractAttributeBag(mixed $slot): ?ComponentAttributeBag
    {
        return match (true) {
            $slot instanceof ComponentAttributeBag => $slot,
            $slot instanceof ComponentSlot => $slot->attributes,
            default => null,
        };
    }
}
