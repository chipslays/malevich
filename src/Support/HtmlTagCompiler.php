<?php

declare(strict_types=1);

namespace Malevich\Support;

use InvalidArgumentException;
use Malevich\Malevich;
use Malevich\MalevichManager;

/**
 * Compiles `@attributes` on plain HTML tags.
 *
 * The element is resolved right before the tag, so debug mode can print an
 * explanation in front of it, and printed inside the tag:
 *
 *     <?php $__malevichAttributes = ...; echo $__malevichAttributes->debugComment(); ?><span<?php echo $__malevichAttributes->inTag(); ?>>
 *
 * A `class="..."` next to `@attributes` is the element's own classes: it
 * works like `@base`, so the option maps go on top of it. It is moved into
 * the `class:` argument first, so the tag never gets two `class` attributes
 * (the browser would silently drop the second one):
 *
 *     <span @attributes('chip') class="size-4 {{ $extra }}">
 *     -> @attributes('chip', class: 'size-4 '.e($extra))
 *
 * `<x-...>` tags are handled by ComponentTagCompiler.
 */
final class HtmlTagCompiler
{
    public static function compile(string $template): string
    {
        $directive = Malevich::directive('attributes');

        if (! str_contains($template, '@'.$directive)) {
            return $template;
        }

        return TagScanner::replaceTags($template, '(?!x[-:])[a-zA-Z][\w\-:.]*', function (array $tag) use ($directive): string {
            $attributes = self::rewrite($tag['name'], $tag['attributes'], $directive);
            $found = TagScanner::findDirective($attributes, $directive);

            if ($found === null) {
                return $attributes === $tag['attributes'] ? $tag[0] : TagScanner::rebuild($tag, $attributes);
            }

            // The whitespace in front goes too: inTag() adds a space only when
            // there is something to print, so an empty element gives `<svg>`.
            $start = $found['offset'];

            while ($start > 0 && ctype_space($attributes[$start - 1])) {
                $start--;
            }

            $attributes = substr_replace($attributes, '<?php echo $'.MalevichManager::ATTRIBUTES_VARIABLE.'->inTag(); ?>', $start, $found['offset'] + $found['length'] - $start);

            return TagScanner::resolveBefore($found['arguments']).TagScanner::rebuild($tag, $attributes);
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

        if (preg_match('/\bclass\s*:/', $arguments)) {
            throw new InvalidArgumentException("Malevich: <{$tag}> has both class=\"...\" and @{$directive}(class: ...). Keep one of them.");
        }

        $replacement = "@{$directive}(".($arguments === '' ? '' : "{$arguments}, ").'class: '.self::expression($tag, $class['value'], $directive).')';

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
     * Turn `size-4 {{ $extra }}` into the PHP expression `'size-4 '.e($extra)`.
     */
    private static function expression(string $tag, string $value, string $directive): string
    {
        $parts = preg_split('/(\{\{.*?\}\}|\{!!.*?!!\})/s', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $expressions = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '{!!')) {
                throw new InvalidArgumentException("Malevich: <{$tag}> uses {!! !!} inside class=\"...\" next to @{$directive}. Use {{ }} or @{$directive}(class: ...).");
            }

            if (str_starts_with($part, '{{')) {
                $expressions[] = 'e('.trim(substr($part, 2, -2)).')';

                continue;
            }

            if (preg_match('/@\w|<\?/', $part)) {
                throw new InvalidArgumentException("Malevich: <{$tag}> has a Blade directive inside class=\"...\" next to @{$directive}. Use @{$directive}(class: ...) instead.");
            }

            $expressions[] = var_export($part, true);
        }

        return $expressions === [] ? "''" : implode('.', $expressions);
    }
}
