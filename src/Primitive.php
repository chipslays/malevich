<?php

declare(strict_types=1);

namespace Malevich;

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

/**
 * The logic behind <x-malevich::primitive>: picks the tag and adds the
 * attributes that make it behave correctly (link vs button, disabled
 * state, safe external links). Holds no styles.
 */
final class Primitive
{
    /**
     * Elements that can't have content and render as `<tag />`.
     */
    private const VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];

    /**
     * Elements that understand the native `disabled` attribute.
     */
    private const DISABLEABLE = ['button', 'fieldset', 'input', 'optgroup', 'option', 'select', 'textarea'];

    public readonly string $tag;

    public readonly ComponentAttributeBag $attributes;

    public function __construct(ComponentAttributeBag $attributes, ?string $as = null, bool $disabled = false)
    {
        $this->tag = $this->resolveTag($as, $attributes->has('href'));
        $this->attributes = $this->resolveAttributes($attributes, $disabled);
    }

    public function isVoid(): bool
    {
        return in_array($this->tag, self::VOID, true);
    }

    /**
     * `as` wins, except that anything with an `href` must be a link:
     * `<x-button href="/home">` renders `<a>`, not `<button href>`.
     */
    private function resolveTag(?string $as, bool $hasHref): string
    {
        $tag = $as ?: ($hasHref ? 'a' : 'div');

        if ($hasHref && $tag === 'button') {
            $tag = 'a';
        }

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $tag)) {
            throw new InvalidArgumentException("[{$tag}] is not a valid tag name for <x-malevich::primitive as=\"...\">.");
        }

        return strtolower($tag);
    }

    private function resolveAttributes(ComponentAttributeBag $attributes, bool $disabled): ComponentAttributeBag
    {
        $defaults = [];

        if ($this->tag === 'button') {
            $defaults['type'] = 'button';
        }

        if ($this->tag === 'a' && $attributes->get('target') === '_blank') {
            $defaults['rel'] = 'noopener noreferrer';
        }

        if ($disabled) {
            $defaults['data-disabled'] = '';
            $defaults += in_array($this->tag, self::DISABLEABLE, true)
                ? ['disabled' => true]
                : ['aria-disabled' => 'true', 'tabindex' => '-1'];
        }

        // Passed attributes come first and win over the defaults.
        $attributes = new ComponentAttributeBag($attributes->getAttributes() + $defaults);

        // A disabled link must not be followable.
        return $disabled && $this->tag === 'a' ? $attributes->except('href') : $attributes;
    }

    public function open(): HtmlString
    {
        $attributes = $this->attributes->isEmpty() ? '' : ' '.$this->attributes->toHtml();

        return new HtmlString("<{$this->tag}{$attributes}".($this->isVoid() ? ' />' : '>'));
    }

    public function close(): HtmlString
    {
        return new HtmlString($this->isVoid() ? '' : "</{$this->tag}>");
    }
}
