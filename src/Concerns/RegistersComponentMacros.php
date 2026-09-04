<?php

declare(strict_types=1);

namespace Malevich\Concerns;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Selector;
use Malevich\Support\DirectiveRegistry;

/**
 * Registers the fluent Selector API onto ComponentAttributeBag (`for`,
 * `use`, `directive`, `preset`), plus the two generic Blade directives -
 * `@directive` and `@preset` - used to declare directive configuration and
 * reusable presets from within a component's own Blade template.
 */
trait RegistersComponentMacros
{
    protected function registerComponentMacros(): void
    {
        ComponentAttributeBag::macro('for', function (string $target) {
            /** @var ComponentAttributeBag $this */
            return (new Selector($this))->for($target);
        });

        ComponentAttributeBag::macro('use', function (array|string $choicesOrTarget = [], array $choices = []) {
            /** @var ComponentAttributeBag $this */
            return (new Selector($this))->use($choicesOrTarget, $choices);
        });

        ComponentAttributeBag::macro('directive', function (string $directive, mixed $value) {
            /** @var ComponentAttributeBag $this */
            return (new Selector($this))->directive($directive, $value);
        });

        ComponentAttributeBag::macro('preset', function (string $name) {
            /** @var ComponentAttributeBag $this */
            return (new Selector($this))->preset($name);
        });
    }

    protected function registerDirectiveBladeDirectives(): void
    {
        Blade::directive('directive', function (string $expression) {
            return "<?php \\".DirectiveRegistry::class."::registerDirective(\$attributes, {$expression}); ?>";
        });

        Blade::directive('preset', function (string $expression) {
            return "<?php \\".DirectiveRegistry::class."::registerPreset({$expression}); ?>";
        });
    }
}
