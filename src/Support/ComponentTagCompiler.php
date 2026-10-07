<?php

declare(strict_types=1);

namespace Malevich\Support;

use Malevich\Malevich;

/**
 * Makes `@ui` work inside component tags:
 *
 *     <x-malevich::primitive as="button" @ui('icon')>
 *
 * Blade never runs directives inside `<x-...>` tags, so before the
 * template is compiled `@ui(...)` is rewritten into an `:attributes`
 * binding, which Blade forwards to the child component as its attributes:
 *
 *     <x-malevich::primitive as="button" :attributes="\Malevich\Malevich::ui($attributes, get_defined_vars(), 'icon')->toAttributeBag()">
 */
final class ComponentTagCompiler
{
    public static function compile(string $template): string
    {
        $directive = preg_quote(Malevich::renderDirective(), '/');

        if (! preg_match("/<\s*x[-:][^>]*@{$directive}\b/", $template)) {
            return $template;
        }

        $tag = "/
            @verbatim.*?@endverbatim(*SKIP)(*FAIL)
            |
            <\s*x[-:][\w\-:.]*
            (?<attributes>
                (?:
                    \s+
                    (?:
                        @\w+(?<parens>\((?:(?>[^()]+)|(?&parens))*\))?
                        | \{\{.*?\}\}
                        | [\w\-:.@%]+(?:=(?:\"[^\"]*\"|'[^']*'|[^'\"=<>\s]+))?
                    )
                )*
                \s*
            )
            \/?>
        /xs";

        return (string) preg_replace_callback($tag, function (array $match) use ($directive): string {
            $attributes = (string) preg_replace_callback(
                "/(?<=\s)@{$directive}(?<parens>\((?:(?>[^()]+)|(?&parens))*\))?(?=\s|$)/",
                fn (array $ui) => self::binding($ui['parens'] ?? ''),
                $match['attributes'],
            );

            return str_replace($match['attributes'], $attributes, $match[0]);
        }, $template);
    }

    private static function binding(string $parens): string
    {
        $arguments = trim(substr($parens, 1, -1));
        $arguments = $arguments === '' ? '' : ", {$arguments}";

        return ':attributes="\\'.Malevich::class."::ui(\$attributes, get_defined_vars(){$arguments})->toAttributeBag()\"";
    }
}
