<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Everything a single component instance declared about its styling:
 * the class map of every option (@variant, @color, @size, ...), compound
 * rules (@compound) and base classes (@base), grouped by target.
 *
 * A target is a named, separately styled element of the component
 * ("icon", "label", ...). Declarations without a target belong to the
 * component's root element.
 */
final class Recipe
{
    /**
     * @var array<string, array<string, mixed>> [target][option] => class map
     */
    private array $options = [];

    /**
     * @var array<string, list<array{0: array<string, mixed>, 1: mixed}>> [target][] => [conditions, classes]
     */
    private array $compounds = [];

    /**
     * @var array<string, list<mixed>> [target][] => classes
     */
    private array $base = [];

    /**
     * @param  string  $defaultTarget  The name of the component's main element.
     */
    public function __construct(private readonly string $defaultTarget = 'default') {}

    /**
     * Classes an element always has. Accepts `(classes)` or `(target, classes)`.
     */
    public function base(mixed ...$args): self
    {
        [$target, $classes] = match (count($args)) {
            1 => [$this->defaultTarget, $args[0]],
            2 => [$args[0], $args[1]],
            default => throw new InvalidArgumentException("@base expects ('classes') or ('target', 'classes')."),
        };

        $this->base[$target][] = $classes;

        return $this;
    }

    /**
     * Accepts `(map)` or `(target, map)`.
     */
    public function option(string $option, mixed ...$args): self
    {
        [$target, $map] = match (count($args)) {
            1 => [$this->defaultTarget, $args[0]],
            2 => [$args[0], $args[1]],
            default => throw new InvalidArgumentException("@{$option} expects ([map]) or ('target', [map])."),
        };

        $this->options[$target][$option] = is_array($map) ? $map : ['*' => $map];

        return $this;
    }

    /**
     * Declare an option grouped by its values instead of by targets:
     *
     *     ->cases('variant', [
     *         'teal' => ['default' => 'bg-teal-600', 'title' => 'text-teal-100'],
     *         'dark' => ['default' => 'bg-gray-900', 'title' => 'text-white'],
     *     ])
     *
     * It fills the same [target][option][value] slots as one `option()` call per
     * target would, and merges into what is already declared for the option.
     * A value may also be plain classes (a string or a list), which style
     * the root element.
     *
     * @param  array<string, mixed>  $cases  [option value] => [target => classes]
     */
    public function cases(string $option, array $cases): self
    {
        foreach ($cases as $value => $targets) {
            if (! is_array($targets) || array_is_list($targets)) {
                $targets = [$this->defaultTarget => $targets];
            }

            foreach ($targets as $target => $classes) {
                $target = (string) $target;
                $existing = $this->options[$target][$option][$value] ?? null;

                $this->options[$target][$option][$value] = $existing === null
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
            2 => [$this->defaultTarget, $args[0], $args[1]],
            3 => $args,
            default => throw new InvalidArgumentException("@compound expects ([conditions], classes) or ('target', [conditions], classes)."),
        };

        $this->compounds[$target][] = [$conditions, $classes];

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
    public function optionsFor(string $target): array
    {
        return $this->options[$target] ?? [];
    }

    /**
     * @return list<array{0: array<string, mixed>, 1: mixed}>
     */
    public function compoundsFor(string $target): array
    {
        return $this->compounds[$target] ?? [];
    }

    /**
     * Every value the option knows about, on any target: the keys of its class
     * maps and the values its @compound conditions test for. Used to tell a
     * typo apart from a value that only styles some of the elements.
     *
     * @return list<mixed>
     */
    public function valuesOf(string $option): array
    {
        $values = [];

        foreach ($this->options as $options) {
            $values = [...$values, ...array_keys($options[$option] ?? [])];
        }

        foreach ($this->compounds as $compounds) {
            foreach ($compounds as [$conditions]) {
                if (array_key_exists($option, $conditions)) {
                    $expected = $conditions[$option];
                    $values = [...$values, ...(is_array($expected) ? array_values($expected) : [$expected])];
                }
            }
        }

        return array_values(array_filter($values, fn (mixed $value) => $value !== '*'));
    }

    public function hasOption(string $option): bool
    {
        foreach ($this->options as $options) {
            if (array_key_exists($option, $options)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Names of every option declared on any target - these are the
     * attributes the component consumes and must not render as HTML.
     *
     * @return list<string>
     */
    public function optionNames(): array
    {
        return array_values(array_unique(array_merge([], ...array_map(array_keys(...), array_values($this->options)))));
    }
}
