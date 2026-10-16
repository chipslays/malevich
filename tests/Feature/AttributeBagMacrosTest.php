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
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    expect($bag->use(['color' => 'red'])->toClasses())->toBe('text-red-500');
});

it('adds an option() macro backed by Element::option()', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    expect($bag->option('color', 'red')->toClasses())->toBe('text-red-500');
});

it('registers one macro per option configured in malevich.options', function () {
    foreach (config('malevich.options') as $option) {
        expect(ComponentAttributeBag::hasMacro($option))->toBeTrue();
    }
});

it('an option macro behaves like ->option(name, value)', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('variant', ['solid' => 'bg-black']);

    expect($bag->variant('solid')->toClasses())->toBe('bg-black');
});
