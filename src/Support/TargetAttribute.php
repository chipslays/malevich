<?php

declare(strict_types=1);

namespace Malevich\Support;

use Illuminate\Support\Str;

/**
 * A target:attribute name, optionally followed by .modifier[argument].
 */
final class TargetAttribute
{
    private const PREFIX = 'malevich-target-';

    /**
     * @param  array{target: string, name: string}  $attribute
     */
    public static function propName(array $attribute): string
    {
        return Str::camel($attribute['target'].'-'.$attribute['name']);
    }

    /**
     * @return array{target: string, name: string, modifiers: array<string, string|bool|null>}|null
     */
    public static function parse(string $name): ?array
    {
        if (str_starts_with($name, self::PREFIX)) {
            $encoded = substr($name, strlen(self::PREFIX));

            if ($encoded === '' || strlen($encoded) % 2 !== 0 || ! ctype_xdigit($encoded)) {
                return null;
            }

            $name = (string) hex2bin($encoded);
        }

        if (! preg_match('/^(?<target>[\w-]+):(?<name>[\w-]+)(?<modifiers>(?:\.[\w-]+(?:\[[^\[\]\s]*\])?)*)$/D', $name, $match)) {
            return null;
        }

        preg_match_all('/\.([\w-]+)(?:\[([^\[\]]*)\])?/', $match['modifiers'], $parts, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
        $modifiers = [];

        foreach ($parts as $part) {
            $modifiers[$part[1]] = match ($part[2]) {
                null, 'true' => true,
                'false' => false,
                '' => null,
                default => $part[2],
            };
        }

        return ['target' => $match['target'], 'name' => $match['name'], 'modifiers' => $modifiers];
    }

    /**
     * Restore transported names after Blade has parsed component tags, before
     * their attributes reach any component (including ordinary Blade views).
     */
    public static function restore(string $template): string
    {
        // Match whole PHP strings so a key-looking fragment inside an
        // attribute value cannot be rewritten. Only array keys are restored.
        $strings = '/"(?:\\\\.|[^"\\\\])*"(*SKIP)(*FAIL)|\'(?:\\\\.|[^\'\\\\])*\'(?<key>\\s*=>)?/s';

        return preg_replace_callback($strings, function (array $match): string {
            if (empty($match['key']) || ! preg_match("/^'(malevich-target-|malevichTarget)([a-f0-9]+)'/", $match[0], $encoded) || strlen($encoded[2]) % 2 !== 0) {
                return $match[0];
            }

            $name = (string) hex2bin($encoded[2]);

            return var_export($encoded[1] === self::PREFIX ? $name : Str::camel($name), true).$match['key'];
        }, $template) ?? $template;
    }

    /**
     * Blade does not accept brackets in attribute names. Transport only the
     * name as hex; Blade still handles values, bindings and escaping itself.
     */
    public static function compile(string $template): string
    {
        if (! str_contains($template, '[')) {
            return $template;
        }

        return TagScanner::replaceTags($template, '\s*x[-:][\w\-:.]*', function (array $tag): string {
            $attributes = preg_replace_callback('/(?:"[^"]*"|\'[^\']*\'|\{\{.*?\}\}|\{!!.*?!!\})(*SKIP)(*FAIL)|(?<=\s)(:?[\w-]+:[\w-]+(?:\.[\w-]+(?:\[[^\[\]\s]*\])?)+)(?==|\s|$)/s', function (array $match): string {
                $name = $match[1];
                $bound = str_starts_with($name, ':');
                $plain = $bound ? substr($name, 1) : $name;

                return str_contains($plain, '[') && self::parse($plain) !== null
                    ? ($bound ? ':' : '').self::PREFIX.bin2hex($plain)
                    : $match[0];
            }, $tag['attributes']) ?? $tag['attributes'];

            return TagScanner::rebuild($tag, $attributes);
        });
    }
}
