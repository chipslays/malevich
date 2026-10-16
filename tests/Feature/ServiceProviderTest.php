<?php

use Illuminate\View\ComponentAttributeBag;

it('merges the package configuration', function () {
    expect(config('malevich.default_target'))->toBe('default')
        ->and(config('malevich.options'))->toBe(['variant', 'size', 'color'])
        ->and(config('malevich.components.prefix'))->toBeNull();
});

it('registers the core attribute-bag macros', function () {
    expect(ComponentAttributeBag::hasMacro('for'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('use'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('option'))->toBeTrue()
        ->and(ComponentAttributeBag::hasMacro('classesFor'))->toBeTrue();
});

it('registers a macro for every option configured in malevich.options', function () {
    foreach (config('malevich.options') as $option) {
        expect(ComponentAttributeBag::hasMacro($option))->toBeTrue();
    }
});
