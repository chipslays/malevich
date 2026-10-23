<?php

declare(strict_types=1);

namespace Malevich\Playground;

/**
 * One rendering in the playground: the tag, and its HTML or why it failed.
 *
 * @internal
 */
final class Cell
{
    /**
     * @param  array<string, string>  $values
     */
    public function __construct(
        public readonly string $tag,
        public readonly array $values,
        public readonly ?string $html,
        public readonly ?string $error = null,
    ) {}

    /**
     * `variant="outline" size="lg"`, or "default" without options.
     */
    public function label(): string
    {
        if ($this->values === []) {
            return 'default';
        }

        return implode(' ', array_map(
            fn (string $option, string $value) => match ($value) {
                'true' => $option,
                'false' => ":{$option}=\"false\"",
                default => "{$option}=\"{$value}\"",
            },
            array_keys($this->values),
            $this->values,
        ));
    }
}
