<?php

declare(strict_types=1);

namespace Malevich\Support;

use InvalidArgumentException;

/**
 * Reads PHP literals without running them: strings, numbers, booleans,
 * null and arrays of those. Anything else (a variable, a function call,
 * a constant, an interpolated string) is refused with an exception, so a
 * caller can tell "this value is X" apart from "this value is unknown".
 *
 * @internal
 */
final class ConstantExpression
{
    /**
     * @var list<array{0: int|string, 1: string}>
     */
    private array $tokens = [];

    private int $position = 0;

    /**
     * Split the arguments of a directive into the source of each argument,
     * e.g. `'icon', ['sm' => 'size-3']` into `'icon'` and `['sm' => 'size-3']`.
     * Each one can then be evaluated - or found unknown - on its own.
     *
     * @return list<string>
     */
    public static function split(string $arguments): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        foreach (token_get_all('<?php '.$arguments) as $token) {
            [$type, $text] = is_array($token) ? [$token[0], $token[1]] : [$token, $token];

            if ($type === T_OPEN_TAG) {
                continue;
            }

            $depth += match ($type) {
                '(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES => 1,
                ')', ']', '}' => -1,
                default => 0,
            };

            if ($type === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $text;
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Read an array's literal keys while leaving its values as source text.
     * Dynamic values are safe; spreads and dynamic keys make the keys unknown.
     *
     * @return array<array-key, string>
     */
    public static function entries(string $expression): array
    {
        $expression = trim($expression);

        if (str_starts_with($expression, '[') && str_ends_with($expression, ']')) {
            $body = substr($expression, 1, -1);
        } elseif (preg_match('/^array\s*\((.*)\)$/s', $expression, $match)) {
            $body = $match[1];
        } else {
            throw new InvalidArgumentException('Not an array literal.');
        }

        $entries = [];

        foreach (self::split($body) as $entry) {
            $depth = 0;
            $offset = 0;
            $arrow = null;

            foreach (token_get_all('<?php '.$entry) as $token) {
                [$type, $text] = is_array($token) ? [$token[0], $token[1]] : [$token, $token];

                if ($type === T_OPEN_TAG) {
                    continue;
                }

                if ($depth === 0 && $type === T_ELLIPSIS) {
                    throw new InvalidArgumentException('Unknown spread keys.');
                }

                if ($depth === 0 && $type === T_DOUBLE_ARROW) {
                    $arrow = $offset;

                    break;
                }

                $depth += match ($type) {
                    '(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES => 1,
                    ')', ']', '}' => -1,
                    default => 0,
                };
                $offset += strlen($text);
            }

            if ($arrow === null) {
                $entries[] = $entry;

                continue;
            }

            $key = self::evaluate(substr($entry, 0, $arrow));

            if (! is_string($key) && ! is_int($key)) {
                throw new InvalidArgumentException('Not a literal array key.');
            }

            $entries[$key] = trim(substr($entry, $arrow + 2));
        }

        return $entries;
    }

    /**
     * @throws InvalidArgumentException When the expression is not a literal.
     */
    public static function evaluate(string $expression): mixed
    {
        $parser = new self;

        foreach (token_get_all('<?php '.$expression) as $token) {
            $token = is_array($token) ? [$token[0], $token[1]] : [$token, $token];

            if (! in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $parser->tokens[] = $token;
            }
        }

        $value = $parser->value();

        if ($parser->position !== count($parser->tokens)) {
            $parser->fail();
        }

        return $value;
    }

    private function value(): mixed
    {
        [$type, $text] = $this->tokens[$this->position] ?? $this->fail();
        $this->position++;

        $value = match (true) {
            $type === '[' => $this->items(']'),
            $type === T_ARRAY => $this->arrayCall(),
            $type === T_CONSTANT_ENCAPSED_STRING => self::string($text),
            $type === T_LNUMBER => (int) $text,
            $type === T_DNUMBER => (float) $text,
            $type === '-' => -$this->number(),
            $type === T_STRING && in_array(strtolower($text), ['true', 'false', 'null'], true) => match (strtolower($text)) {
                'true' => true,
                'false' => false,
                default => null,
            },
            default => $this->fail(),
        };

        // 'a '.'b' - plain concatenation of literals.
        while (($this->tokens[$this->position][0] ?? null) === '.') {
            $this->position++;
            $next = $this->value();

            if (! is_scalar($value) || ! is_scalar($next)) {
                $this->fail();
            }

            $value = $value.$next;
        }

        return $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function items(string $close): array
    {
        $items = [];

        while (($this->tokens[$this->position][0] ?? null) !== $close) {
            $value = $this->value();

            if (($this->tokens[$this->position][0] ?? null) === T_DOUBLE_ARROW) {
                $this->position++;

                if (! is_int($value) && ! is_string($value) && ! is_bool($value) && $value !== null) {
                    $this->fail();
                }

                $items[(string) (is_bool($value) ? (int) $value : $value)] = $this->value();
            } else {
                $items[] = $value;
            }

            if (($this->tokens[$this->position][0] ?? null) === ',') {
                $this->position++;
            } elseif (($this->tokens[$this->position][0] ?? null) !== $close) {
                $this->fail();
            }
        }

        $this->position++;

        return $items;
    }

    private function number(): int|float
    {
        $value = $this->value();

        return is_int($value) || is_float($value) ? $value : $this->fail();
    }

    /**
     * `array(...)`, the long form of `[...]`.
     *
     * @return array<array-key, mixed>
     */
    private function arrayCall(): array
    {
        if (($this->tokens[$this->position][0] ?? null) !== '(') {
            $this->fail();
        }

        $this->position++;

        return $this->items(')');
    }

    private static function string(string $literal): string
    {
        $body = substr($literal, 1, -1);

        return $literal[0] === "'"
            ? strtr($body, ['\\\\' => '\\', "\\'" => "'"])
            : stripcslashes(str_replace('\\$', '$', $body));
    }

    private function fail(): never
    {
        throw new InvalidArgumentException('Not a literal.');
    }
}
