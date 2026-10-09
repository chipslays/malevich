<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Everything a single component instance declared about its styling:
 * the class map of every axis (@variant, @color, @size, ...), compound
 * rules (@compound) and presets (@preset), grouped by target.
 *
 * A target is a named, separately styled element of the component
 * ("icon", "label", ...). Declarations without a target belong to the
 * component's root element.
 */
final class Recipe
{
    /**
     * @var array<string, array<string, mixed>> [target][axis] => class map
     */
    private array $axes = [];

    /**
     * @var array<string, list<array{0: array<string, mixed>, 1: mixed}>> [target][] => [conditions, classes]
     */
    private array $compounds = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $presets = [];

    /**
     * @var array<string, list<mixed>> [target][] => classes
     */
    private array $base = [];

    /**
     * Classes an element always has. Accepts `(classes)` or `(target, classes)`.
     */
    public function base(mixed ...$args): self
    {
        [$target, $classes] = match (count($args)) {
            1 => [Malevich::defaultTarget(), $args[0]],
            2 => [$args[0], $args[1]],
            default => throw new InvalidArgumentException("@base expects ('classes') or ('target', 'classes')."),
        };

        $this->base[$target][] = $classes;

        return $this;
    }

    /**
     * Accepts `(map)` or `(target, map)`.
     */
    public function axis(string $axis, mixed ...$args): self
    {
        [$target, $map] = match (count($args)) {
            1 => [Malevich::defaultTarget(), $args[0]],
            2 => [$args[0], $args[1]],
            default => throw new InvalidArgumentException("@{$axis} expects ([map]) or ('target', [map])."),
        };

        $this->axes[$target][$axis] = is_array($map) ? $map : ['*' => $map];

        return $this;
    }

    /**
     * Declare an axis grouped by its values instead of by targets:
     *
     *     ->cases('variant', [
     *         'teal' => ['default' => 'bg-teal-600', 'title' => 'text-teal-100'],
     *         'dark' => ['default' => 'bg-gray-900', 'title' => 'text-white'],
     *     ])
     *
     * It fills the same [target][axis][value] slots as one `axis()` call per
     * target would, and merges into what is already declared for the axis.
     * A value may also be plain classes (a string or a list), which style
     * the root element.
     *
     * @param  array<string, mixed>  $cases  [axis value] => [target => classes]
     */
    public function cases(string $axis, array $cases): self
    {
        foreach ($cases as $value => $targets) {
            if (! is_array($targets) || array_is_list($targets)) {
                $targets = [Malevich::defaultTarget() => $targets];
            }

            foreach ($targets as $target => $classes) {
                $target = (string) $target;
                $existing = $this->axes[$target][$axis][$value] ?? null;

                $this->axes[$target][$axis][$value] = $existing === null
                    ? $classes
                    : trim(Arr::toCssClasses($existing).' '.Arr::toCssClasses($classes));
            }
        }

        return $this;
    }

    /**
     * Accepts `(conditions, classes)` or `(target, conditions, classes)`.
     */
    public function compound(mixed ...$args): self
    {
        [$target, $conditions, $classes] = match (count($args)) {
            2 => [Malevich::defaultTarget(), $args[0], $args[1]],
            3 => $args,
            default => throw new InvalidArgumentException("@compound expects ([conditions], classes) or ('target', [conditions], classes)."),
        };

        $this->compounds[$target][] = [$conditions, $classes];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $values  Axis values, either flat or keyed by target.
     */
    public function preset(string $name, array $values): self
    {
        $this->presets[$name] = $values;

        return $this;
    }

    /**
     * @return list<mixed>
     */
    public function baseFor(string $target): array
    {
        return $this->base[$target] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function axesFor(string $target): array
    {
        return $this->axes[$target] ?? [];
    }

    /**
     * @return list<array{0: array<string, mixed>, 1: mixed}>
     */
    public function compoundsFor(string $target): array
    {
        return $this->compounds[$target] ?? [];
    }

    /**
     * Preset values for the given target. A preset keyed by target names
     * yields that target's values, a flat preset applies to every target.
     *
     * @return array<string, mixed>
     */
    public function presetFor(string $name, string $target): array
    {
        $preset = $this->presets[$name] ?? [];

        return is_array($preset[$target] ?? null) ? $preset[$target] : $preset;
    }

    public function hasPresets(): bool
    {
        return $this->presets !== [];
    }

    public function hasAxis(string $axis): bool
    {
        foreach ($this->axes as $axes) {
            if (array_key_exists($axis, $axes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Names of every axis declared on any target - these are the
     * attributes the component consumes and must not render as HTML.
     *
     * @return list<string>
     */
    public function axisNames(): array
    {
        return array_values(array_unique(array_merge([], ...array_map(array_keys(...), array_values($this->axes)))));
    }
}
