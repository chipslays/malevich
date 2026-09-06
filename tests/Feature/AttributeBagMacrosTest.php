<?php

use Illuminate\View\ComponentAttributeBag;
use Malevich\Selector;
use Malevich\Support\DirectiveRegistry;

it('adds a for() macro that returns a Selector scoped to the given target', function () {
    $bag = new ComponentAttributeBag;

    expect($bag->for('icon'))->toBeInstanceOf(Selector::class);
});

it('adds a use() macro backed by Selector::use()', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    expect($bag->use(['color' => 'red'])->toClasses())->toBe('text-red-500');
});

it('adds a directive() macro backed by Selector::directive()', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    expect($bag->directive('color', 'red')->toClasses())->toBe('text-red-500');
});

it('adds a preset() macro backed by Selector::preset()', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);
    DirectiveRegistry::registerPreset('danger', ['color' => 'red']);

    expect($bag->preset('danger')->toClasses())->toBe('text-red-500');
});

it('registers one macro per directive configured in malevich.directives', function () {
    foreach (config('malevich.directives') as $directive) {
        expect(ComponentAttributeBag::hasMacro($directive))->toBeTrue();
    }
});

it('a dynamic directive macro behaves like ->directive(name, value)', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'variant', ['solid' => 'bg-black']);

    expect($bag->variant('solid')->toClasses())->toBe('bg-black');
});
