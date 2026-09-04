<?php

declare(strict_types=1);

namespace Malevich\Concerns;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Selector;
use Malevich\Support\DirectiveRegistry;

/**
 * Registers a Blade directive - and, unless a macro of the same name
 * already exists, a matching ComponentAttributeBag macro - for every
 * directive name configured under `malevich.directives` (by default
 * `variant`, `color` and `size`).
 *
 * Each generated directive is sugar for `@directive('name', ...)` with the
 * directive name baked in, e.g. `@variant([...])` instead of
 * `@directive('variant', [...])`.
 */
trait RegistersDynamicDirectives
{
    protected function registerDynamicDirectives(): void
    {
        foreach ($this->configuredDirectives() as $directive) {
            $this->registerDynamicBladeDirective($directive);
            $this->registerDynamicMacro($directive);
        }
    }

    /**
     * @return list<string>
     */
    protected function configuredDirectives(): array
    {
        return config('malevich.directives', ['variant', 'color', 'size']);
    }

    protected function registerDynamicBladeDirective(string $directive): void
    {
        Blade::directive($directive, function (string $expression) use ($directive) {
            return "<?php \\".DirectiveRegistry::class."::registerDirective(\$attributes, '{$directive}', {$expression}); ?>";
        });
    }

    protected function registerDynamicMacro(string $directive): void
    {
        if (ComponentAttributeBag::hasMacro($directive)) {
            return;
        }

        ComponentAttributeBag::macro($directive, function (mixed $value) use ($directive) {
            /** @var ComponentAttributeBag $this */
            return (new Selector($this))->directive($directive, $value);
        });
    }
}
