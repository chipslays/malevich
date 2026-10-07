<?php

use Illuminate\View\ComponentAttributeBag;
use Malevich\Element;
use Malevich\Malevich;

it('adds a for() macro that returns a Element scoped to the given target', function () {
    $bag = new ComponentAttributeBag;

    expect($bag->for('icon'))->toBeInstanceOf(Element::class);
});

it('adds a use() macro backed by Element::use()', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->axis('color', ['red' => 'text-red-500']);

    expect($bag->use(['color' => 'red'])->toClasses())->toBe('text-red-500');
});

it('adds a directive() macro backed by Element::directive()', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->axis('color', ['red' => 'text-red-500']);

    expect($bag->directive('color', 'red')->toClasses())->toBe('text-red-500');
});

it('adds a preset() macro backed by Element::preset()', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->axis('color', ['red' => 'text-red-500']);
    Malevich::recipe($bag)->preset('danger', ['color' => 'red']);

    expect($bag->preset('danger')->toClasses())->toBe('text-red-500');
});

it('registers one macro per directive configured in malevich.directives', function () {
    foreach (config('malevich.directives') as $directive) {
        expect(ComponentAttributeBag::hasMacro($directive))->toBeTrue();
    }
});

it('a dynamic directive macro behaves like ->directive(name, value)', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->axis('variant', ['solid' => 'bg-black']);

    expect($bag->variant('solid')->toClasses())->toBe('bg-black');
});
