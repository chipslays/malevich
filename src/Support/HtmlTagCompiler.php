<?php

declare(strict_types=1);

namespace Malevich\Support;

use InvalidArgumentException;
use Malevich\Malevich;

/**
 * Keeps `class="..."` and `@attributes` on the same plain HTML tag from producing
 * two `class` attributes (the browser would silently drop the second one):
 *
 *     <span @attributes('chip') class="size-4 {{ $extra }}">
 *
 * The class value is moved into the `merge:` argument of `@attributes`, which adds
 * it after the element's own classes, exactly like a class on a component tag:
 *
 *     <span @attributes('chip', merge: ['class' => 'size-4 '.(string) ($extra)])>
 *
 * `<x-...>` tags are not touched: Blade already merges their attributes.
 */
final class HtmlTagCompiler
{
    public static function compile(string $template): string
    {
        $directive = Malevich::directive('attributes');

        if (! str_contains($template, '@'.$directive) || ! str_contains($template, 'class')) {
            return $template;
        }

        return TagScanner::replaceTags($template, '(?!x[-:])[a-zA-Z][\w\-:.]*', function (array $tag) use ($directive): string {
            $attributes = self::rewrite($tag['name'], $tag['attributes'], $directive);

            return $attributes === $tag['attributes'] ? $tag[0] : TagScanner::rebuild($tag, $attributes);
        });
    }

    private static function rewrite(string $tag, string $attributes, string $directive): string
    {
        $classes = TagScanner::findClasses($attributes);

        if ($classes === []) {
            return $attributes;
        }

        $found = TagScanner::findDirective($attributes, $directive);

        if ($found === null) {
            return $attributes;
        }

        if (count($classes) > 1) {
            throw new InvalidArgumentException("Malevich: <{$tag}> has more than one class attribute next to @{$directive}.");
        }

        [$class] = $classes;
        $arguments = $found['arguments'];

        if (preg_match('/\bmerge\s*:/', $arguments)) {
            throw new InvalidArgumentException(
                "Malevich: <{$tag}> has class=\"{$class['value']}\" next to @{$directive}(... merge: ...). Put the classes into merge: ['class' => ...] instead.",
            );
        }

        $replacement = "@{$directive}(".($arguments === '' ? '' : "{$arguments}, ")."merge: ['class' => ".self::expression($tag, $class['value'], $directive).'])';

        // The class attribute goes together with the whitespace in front of it.
        $start = $class['offset'];

        while ($start > 0 && ctype_space($attributes[$start - 1])) {
            $start--;
        }

        // The later offset goes first, so the earlier one stays valid.
        $edits = [
            [$start, $class['offset'] + $class['length'] - $start, ''],
            [$found['offset'], $found['length'], $replacement],
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
    private static function expression(string $tag, string $value, string $directive): string
    {
        $parts = preg_split('/(\{\{.*?\}\}|\{!!.*?!!\})/s', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $expressions = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '{!!')) {
                throw new InvalidArgumentException("Malevich: <{$tag}> uses {!! !!} inside class=\"...\" next to @{$directive}. Use {{ }} or merge: ['class' => ...].");
            }

            if (str_starts_with($part, '{{')) {
                $expressions[] = '(string) ('.trim(substr($part, 2, -2)).')';

                continue;
            }

            if (preg_match('/@\w|<\?/', $part)) {
                throw new InvalidArgumentException("Malevich: <{$tag}> has a Blade directive inside class=\"...\" next to @{$directive}. Use merge: ['class' => ...] instead.");
            }

            $expressions[] = var_export($part, true);
        }

        return $expressions === [] ? "''" : implode('.', $expressions);
    }
}
