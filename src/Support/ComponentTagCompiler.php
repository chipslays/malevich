<?php

declare(strict_types=1);

namespace Malevich\Support;

use Malevich\Malevich;
use Malevich\MalevichManager;

/**
 * Makes `@attributes` work inside component tags:
 *
 *     <x-malevich::primitive as="button" @attributes('icon')>
 *
 * Blade never runs directives inside `<x-...>` tags, so before the
 * template is compiled the element is resolved right in front of the tag
 * and passed to the child component as its attributes:
 *
 *     <?php $__malevichAttributes = \Malevich\Malevich::attributes($attributes ?? null, get_defined_vars(), 'icon'); echo $__malevichAttributes->debugComment(); ?>
 *     <x-malevich::primitive as="button" :attributes="$__malevichAttributes->toAttributeBag()">
 *
 * Resolving it in plain PHP keeps any quotes in the arguments out of the
 * attribute value, and resolves the element once instead of twice.
 */
final class ComponentTagCompiler
{
    public static function compile(string $template): string
    {
        $directive = Malevich::directive('attributes');

        if (! str_contains($template, '@'.$directive) || ! preg_match('/<\s*x[-:]/', $template)) {
            return $template;
        }

        return TagScanner::replaceTags($template, '\s*x[-:][\w\-:.]*', function (array $tag) use ($directive): string {
            $found = TagScanner::findDirective($tag['attributes'], $directive);

            if ($found === null) {
                return $tag[0];
            }

            $variable = '$'.MalevichManager::ATTRIBUTES_VARIABLE;
            $attributes = substr_replace($tag['attributes'], ":attributes=\"{$variable}->toAttributeBag()\"", $found['offset'], $found['length']);

            return TagScanner::resolveBefore($found['arguments']).TagScanner::rebuild($tag, $attributes);
        });
    }
}
