<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Everything a single component instance declared about its styling:
 * the class map of every axis (@variant, @color, @size, ...), compound
 * rules (@compound), presets (@preset) and default values (@defaults),
 * grouped by target.
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
     * @var array<string, mixed> [axis] => value, for every target
     */
    private array $defaults = [];

    /**
     * @var array<string, array<string, mixed>> [target][axis] => value
     */
    private array $targetDefaults = [];

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
     * The value an axis takes when nothing else picks one: not the template,
     * not the tag, not a preset. Accepts `(values)`, which applies to every
     * target, or `(target, values)`, which applies to that target only.
     * Later calls add to earlier ones.
     */
    public function defaults(mixed ...$args): self
    {
        [$target, $values] = match (count($args)) {
            1 => [null, $args[0]],
            2 => [$args[0], $args[1]],
            default => throw new InvalidArgumentException("@defaults expects ([values]) or ('target', [values])."),
        };

        if (! is_array($values)) {
            throw new InvalidArgumentException("@defaults expects an array of values, e.g. ['variant' => 'primary', 'size' => 'md'].");
        }

        if ($target === null) {
            $this->defaults = array_merge($this->defaults, $values);
        } else {
            $this->targetDefaults[$target] = array_merge($this->targetDefaults[$target] ?? [], $values);
        }

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

    /**
     * Default values for the given target: the ones declared for every
     * target, overridden by the ones declared for this target.
     *
     * @return array<string, mixed>
     */
    public function defaultsFor(string $target): array
    {
        return array_merge($this->defaults, $this->targetDefaults[$target] ?? []);
    }

    /**
     * Every value the axis knows about, on any target: the keys of its class
     * maps and the values its @compound conditions test for. Used to tell a
     * typo apart from a value that only styles some of the elements.
     *
     * @return list<mixed>
     */
    public function valuesOf(string $axis): array
    {
        $values = [];

        foreach ($this->axes as $axes) {
            $values = [...$values, ...array_keys($axes[$axis] ?? [])];
        }

        foreach ($this->compounds as $compounds) {
            foreach ($compounds as [$conditions]) {
                if (array_key_exists($axis, $conditions)) {
                    $expected = $conditions[$axis];
                    $values = [...$values, ...(is_array($expected) ? array_values($expected) : [$expected])];
                }
            }
        }

        return array_values(array_filter($values, fn (mixed $value) => $value !== '*'));
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
