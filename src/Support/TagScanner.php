<?php

declare(strict_types=1);

namespace Malevich\Support;

use Closure;

/**
 * The regex building blocks shared by the tag compilers: finding the tags
 * of a template and the `@ui(...)` / `class="..."` among their attributes.
 */
final class TagScanner
{
    /**
     * Balanced parentheses. Quoted strings inside may hold any character,
     * so `@ui(merge: ['title' => 'a)b'])` is read as a whole.
     */
    public const PARENS = '(?<parens>\((?:[^()\'"]++|\'(?:[^\'\\\\]|\\\\.)*+\'|"(?:[^"\\\\]|\\\\.)*+"|(?&parens))*+\))';

    /**
     * Quoted values and echoes inside a tag, which are text, not attributes.
     */
    private const QUOTED = '"[^"]*"|\'[^\']*\'|\{\{.*?\}\}|\{!!.*?!!\}';

    /**
     * Replace every tag whose name matches $name. The callback receives the
     * match (`name`, `attributes`, and `0` for the whole tag) and returns
     * the replacement. If the regex gives up (e.g. on a huge template), the
     * template is returned untouched rather than emptied.
     *
     * @param  Closure(array<int|string, string>): string  $callback
     */
    public static function replaceTags(string $template, string $name, Closure $callback): string
    {
        $pattern = '/
            @verbatim.*?@endverbatim(*SKIP)(*FAIL)
            |
            \{\{--.*?--\}\}(*SKIP)(*FAIL)
            |
            <(?<name>'.$name.')
            (?<attributes>
                (?:
                    \s+
                    (?:
                        @\w+'.self::PARENS.'?
                        | \{\{.*?\}\}
                        | \{!!.*?!!\}
                        | [\w\-:.@%]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\'"=<>\s]+))?
                    )
                )*
                \s*
            )
            \/?>
        /xs';

        return preg_replace_callback($pattern, $callback, $template) ?? $template;
    }

    /**
     * The tag with its attributes replaced, everything else kept as it was.
     *
     * @param  array<int|string, string>  $tag  A match from replaceTags().
     */
    public static function rebuild(array $tag, string $attributes): string
    {
        return '<'.$tag['name'].$attributes.substr($tag[0], 1 + strlen($tag['name']) + strlen($tag['attributes']));
    }

    /**
     * Find `@directive` or `@directive(...)` among the attributes of a tag,
     * skipping quoted values and echoes.
     *
     * @return array{offset: int, length: int, arguments: string}|null
     */
    public static function findDirective(string $attributes, string $directive): ?array
    {
        $pattern = '/(?:'.self::QUOTED.')(*SKIP)(*FAIL)|(?<=\s)@'.preg_quote($directive, '/').self::PARENS.'?(?=\s|$)/s';

        if (! preg_match($pattern, $attributes, $found, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $parens = $found['parens'][0] ?? '';

        return [
            'offset' => $found[0][1],
            'length' => strlen($found[0][0]),
            'arguments' => trim(substr($parens === '' ? '()' : $parens, 1, -1)),
        ];
    }

    /**
     * Every `class=...` attribute of a tag (quoted or not), skipping quoted
     * values and echoes.
     *
     * @return list<array{offset: int, length: int, value: string}>
     */
    public static function findClasses(string $attributes): array
    {
        $pattern = '/(?:'.self::QUOTED.')(*SKIP)(*FAIL)|(?<=\s)class=(?:"(?<double>[^"]*)"|\'(?<single>[^\']*)\'|(?<bare>[^\'"=<>\s]+))/s';

        preg_match_all($pattern, $attributes, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        return array_map(fn (array $match) => [
            'offset' => (int) $match[0][1],
            'length' => strlen((string) $match[0][0]),
            'value' => (string) ($match['double'][0] ?? $match['single'][0] ?? $match['bare'][0] ?? ''),
        ], $matches);
    }
}
