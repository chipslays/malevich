<?php

use Illuminate\View\ComponentAttributeBag;

it('merges the package configuration', function () {
    expect(config('malevich.default_target'))->toBe('default')
        ->and(config('malevich.directives'))->toBe(['variant', 'size', 'color'])
        ->and(config('malevich.components.prefix'))->toBe('ui');
});

it('registers the core attribute-bag macros', function () {
    expect(ComponentAttributeBag::hasMacro('for'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('use'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('directive'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('preset'))->toBeTrue();
});

it('registers a macro for every directive configured in malevich.directives', function () {
    foreach (config('malevich.directives') as $directive) {
        expect(ComponentAttributeBag::hasMacro($directive))->toBeTrue();
    }
});
