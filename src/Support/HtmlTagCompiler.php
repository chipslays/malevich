<?php

declare(strict_types=1);

namespace Malevich\Support;

use InvalidArgumentException;
use Malevich\Malevich;

/**
 * Keeps `class="..."` and `@ui` on the same plain HTML tag from producing
 * two `class` attributes (the browser would silently drop the second one):
 *
 *     <span @ui('chip') class="size-4 {{ $extra }}">
 *
 * The class value is moved into the `merge:` argument of `@ui`, which adds
 * it after the element's own classes, exactly like a class on a component tag:
 *
 *     <span @ui('chip', merge: ['class' => 'size-4 '.(string) ($extra)])>
 *
 * `<x-...>` tags are not touched: Blade already merges their attributes.
 */
final class HtmlTagCompiler
{
    public static function compile(string $template): string
    {
        $directive = preg_quote(Malevich::renderDirective(), '/');

        if (! preg_match("/@{$directive}\b/", $template) || ! str_contains($template, 'class')) {
            return $template;
        }

        $tag = "/
            @verbatim.*?@endverbatim(*SKIP)(*FAIL)
            |
            \{\{--.*?--\}\}(*SKIP)(*FAIL)
            |
            <(?!x[-:])(?<name>[a-zA-Z][\w\-:.]*)
            (?<attributes>
                (?:
                    \s+
                    (?:
                        @\w+(?<parens>\((?:(?>[^()]+)|(?&parens))*\))?
                        | \{\{.*?\}\}
                        | \{!!.*?!!\}
                        | [\w\-:.@%]+(?:=(?:\"[^\"]*\"|'[^']*'|[^'\"=<>\s]+))?
                    )
                )*
                \s*
            )
            \/?>
        /xs";

        // null means the regex gave up (e.g. a huge template): better untouched than empty.
        return preg_replace_callback($tag, function (array $match) use ($directive): string {
            $attributes = self::rewrite($match['name'], $match['attributes'], $directive);

            return $attributes === $match['attributes']
                ? $match[0]
                : str_replace($match['attributes'], $attributes, $match[0]);
        }, $template) ?? $template;
    }

    private static function rewrite(string $tag, string $attributes, string $directive): string
    {
        $skip = "(?:\"[^\"]*\"|'[^']*'|\{\{.*?\}\}|\{!!.*?!!\})(*SKIP)(*FAIL)|";

        $classes = [];
        preg_match_all("/{$skip}(?<=\s)class=(?:\"(?<double>[^\"]*)\"|'(?<single>[^']*)')/s", $attributes, $classes, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($classes === []) {
            return $attributes;
        }

        $ui = "/{$skip}(?<=\s)@{$directive}(?<parens>\((?:(?>[^()]+)|(?&parens))*\))?(?=\s|$)/s";

        if (! preg_match($ui, $attributes, $found, PREG_OFFSET_CAPTURE)) {
            return $attributes;
        }

        if (count($classes) > 1) {
            throw new InvalidArgumentException("Malevich: <{$tag}> has more than one class attribute next to @{$directive}.");
        }

        $value = $classes[0]['double'][0] !== '' ? $classes[0]['double'][0] : ($classes[0]['single'][0] ?? '');
        $arguments = trim(substr($found['parens'][0] ?? '()', 1, -1));

        if (preg_match('/\bmerge\s*:/', $arguments)) {
            throw new InvalidArgumentException(
                "Malevich: <{$tag}> has class=\"{$value}\" next to @{$directive}(... merge: ...). Put the classes into merge: ['class' => ...] instead.",
            );
        }

        $replacement = "@{$directive}(".($arguments === '' ? '' : "{$arguments}, ")."merge: ['class' => ".self::expression($tag, $value).'])';

        // The class attribute goes together with the whitespace in front of it.
        $start = $classes[0][0][1];

        while ($start > 0 && ctype_space($attributes[$start - 1])) {
            $start--;
        }

        // The later offset goes first, so the earlier one stays valid.
        $edits = [
            [$start, $classes[0][0][1] + strlen($classes[0][0][0]) - $start, ''],
            [$found[0][1], strlen($found[0][0]), $replacement],
        ];
        usort($edits, fn (array $a, array $b) => $b[0] <=> $a[0]);

        foreach ($edits as [$offset, $length, $text]) {
            $attributes = substr_replace($attributes, $text, $offset, $length);
        }

        return $attributes;
    }

    /**
     * Turn `size-4 {{ $extra }}` into the PHP expression `'size-4 '.(string) ($extra)`.
     */
    private static function expression(string $tag, string $value): string
    {
        $parts = preg_split('/(\{\{.*?\}\}|\{!!.*?!!\})/s', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $expressions = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '{!!')) {
                throw new InvalidArgumentException("Malevich: <{$tag}> uses {!! !!} inside class=\"...\" next to @ui. Use {{ }} or merge: ['class' => ...].");
            }

            if (str_starts_with($part, '{{')) {
                $expressions[] = '(string) ('.trim(substr($part, 2, -2)).')';

                continue;
            }

            if (preg_match('/@\w|<\?/', $part)) {
                throw new InvalidArgumentException("Malevich: <{$tag}> has a Blade directive inside class=\"...\" next to @ui. Use merge: ['class' => ...] instead.");
            }

            $expressions[] = var_export($part, true);
        }

        return $expressions === [] ? "''" : implode('.', $expressions);
    }
}
