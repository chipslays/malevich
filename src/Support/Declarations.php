<?php

declare(strict_types=1);

namespace Malevich\Support;

use BackedEnum;

/**
 * What the declarations of one component add up to.
 *
 * @internal
 */
final class Declarations
{
    /**
     * @var array<string, array<string, true>> [option][value]
     */
    private array $values = [];

    /**
     * @var array<string, true> Options whose value can't be checked.
     */
    private array $unchecked = [];

    /**
     * @var array<string, true>
     */
    public array $presets = [];

    public bool $presetsKnown = true;

    /**
     * @param  list<mixed>  $values
     */
    public function values(string $axis, array $values): void
    {
        foreach ($values as $value) {
            $value = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                $value instanceof BackedEnum => (string) $value->value,
                is_scalar($value) => (string) $value,
                default => null,
            };

            if ($value !== null && $value !== '*') {
                $this->values[$axis][$value] = true;
            }
        }

        $this->values[$axis] ??= [];
    }

    /**
     * Never check this option: its map is unknown, or any value is fine.
     */
    public function unchecked(string $axis): void
    {
        $this->unchecked[$axis] = true;
    }

    /**
     * The values of every option that can be checked.
     *
     * @return array<string, list<string>>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->values as $axis => $values) {
            if (! isset($this->unchecked[$axis]) && $values !== []) {
                $options[$axis] = array_map(strval(...), array_keys($values));
            }
        }

        ksort($options);

        return $options;
    }
}
