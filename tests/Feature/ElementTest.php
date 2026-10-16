<?php

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Malevich\Element;
use Malevich\Malevich;

it('defaults to the configured default target', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500');
});

it('for() switches the target without mutating the original Element', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $base = new Element($bag);
    $forIcon = $base->for('icon')->option('color', 'red');

    expect($forIcon->toClasses())->toBe('text-red-500')
        ->and($base->option('color', 'red')->toClasses())->toBe('');
});

it('applies a wildcard rule regardless of the selected choice', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('size', [
        '*' => 'inline-flex items-center',
        'sm' => 'text-sm',
    ]);

    $Element = (new Element($bag))->option('size', 'sm');

    expect($Element->toClasses())->toBe('inline-flex items-center text-sm');
});

it('applies only the wildcard rule when no matching choice was selected', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('size', [
        '*' => 'inline-flex',
        'sm' => 'text-sm',
    ]);

    $Element = new Element($bag);

    expect($Element->toClasses())->toBe('inline-flex');
});

it('ignores a selected choice that is not present in the class map', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->option('color', 'blue');

    expect($Element->toClasses())->toBe('');
});

it('use() sets a target and merges choices in one call', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', 'btn', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->use('btn', ['color' => 'red']);

    expect($Element->toClasses())->toBe('text-red-500');
});

it('use() resolves per-target choice arrays against the current target', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $Element = (new Element($bag))
        ->for('icon')
        ->use(['color' => ['icon' => 'red', 'label' => 'blue']]);

    expect($Element->toClasses())->toBe('text-red-500');
});

it('use() merges an explicit extra choices array on top', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);
    Malevich::recipe($bag)->option('size', ['lg' => 'text-lg']);

    $Element = (new Element($bag))->use(['color' => 'red'], ['size' => 'lg']);

    expect($Element->toClasses())->toBe('text-red-500 text-lg');
});

it('option() resolves a per-target value array against the current target', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $Element = (new Element($bag))
        ->for('icon')
        ->option('color', ['icon' => 'red', 'label' => 'blue']);

    expect($Element->toClasses())->toBe('text-red-500');
});

it('supports magic method calls as option shortcuts', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('variant', ['solid' => 'bg-black']);

    $Element = (new Element($bag))->variant('solid');

    expect($Element->toClasses())->toBe('bg-black');
});

it('forwards unknown methods to the attribute bag', function () {
    $bag = new ComponentAttributeBag(['id' => 'x', 'class' => 'own']);
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->color('red');

    expect($Element->getAttributes())->toBe(['class' => 'text-red-500 own', 'id' => 'x'])
        ->and($Element->has('id'))->toBeTrue()
        ->and(fn () => $Element->somethingUndefined())->toThrow(BadMethodCallException::class);
});

it('merges the component own class attribute for the default target', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500 own-class');
});

it('does not leak the component class into a non-default target without an explicit slot', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->for('icon')->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500');
});

it('merges the slot class when a slot is explicitly provided for a non-default target', function () {
    $bag = new ComponentAttributeBag(['class' => 'ignored']);
    $slotBag = new ComponentAttributeBag(['class' => 'slot-class']);
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->for('icon')->slot($slotBag)->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500 slot-class');
});

it('extracts attributes from a ComponentSlot instance passed to slot()', function () {
    $bag = new ComponentAttributeBag;
    $slotAttributes = new ComponentAttributeBag(['class' => 'slot-class']);
    $slot = new ComponentSlot('content', $slotAttributes->getAttributes());
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->for('icon')->slot($slot)->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500 slot-class');
});

it('ignores slot() when given a value it cannot recognise', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    // On the default target, an unrecognised slot() falls back to the
    // component's own attributes rather than merging nothing.
    $Element = (new Element($bag))->slot('not-an-attribute-bag')->option('color', 'red');

    expect($Element->toClasses())->toBe('text-red-500 own-class');
});

it('toHtml renders the resolved class together with passthrough attributes', function () {
    $bag = new ComponentAttributeBag(['id' => 'box', 'class' => 'own-class']);
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $html = (string) (new Element($bag))->option('color', 'red');

    expect($html)->toContain('id="box"')
        ->and($html)->toContain('class="text-red-500 own-class"');
});

it('toHtml renders only the resolved class when there are no own attributes to merge', function () {
    $bag = new ComponentAttributeBag(['id' => 'box']);
    Malevich::recipe($bag)->option('color', 'icon', ['red' => 'text-red-500']);

    $html = (string) (new Element($bag))->for('icon')->option('color', 'red');

    expect($html)->toContain('class="text-red-500"')
        ->and($html)->not->toContain('id="box"');
});

it('__toString delegates to toHtml', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500']);

    $Element = (new Element($bag))->option('color', 'red');

    expect($Element->__toString())->toBe($Element->toHtml());
});

it('applies compound rules with "any of" conditions', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)
        ->option('size', ['sm' => 'p-1', 'md' => 'p-2'])
        ->compound(['size' => ['sm', 'md']], 'rounded');

    expect((new Element($bag))->size('md')->toClasses())->toBe('p-2 rounded')
        ->and((new Element($bag))->toClasses())->toBe('');
});

it('lets an explicit value override the tag, and ignores explicit nulls', function () {
    $bag = new ComponentAttributeBag(['color' => 'red']);
    Malevich::recipe($bag)->option('color', ['red' => 'text-red-500', 'blue' => 'text-blue-500']);

    expect((new Element($bag))->color('blue')->toClasses())->toBe('text-blue-500')
        ->and((new Element($bag))->color(null)->toClasses())->toBe('text-red-500');
});

it('accepts a plain string as a wildcard-only option', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('variant', 'inline-flex');

    expect((new Element($bag))->toClasses())->toBe('inline-flex');
});

it('adds @base classes first, for the root and for targets', function () {
    $bag = new ComponentAttributeBag(['class' => 'own']);
    Malevich::recipe($bag)
        ->option('size', ['sm' => 'p-1'])
        ->base('inline-flex')
        ->base('items-center')
        ->base('title', 'font-semibold');

    expect((new Element($bag))->size('sm')->toClasses())->toBe('inline-flex items-center p-1 own')
        ->and((new Element($bag))->for('title')->toClasses())->toBe('font-semibold');
});

it('does not treat @base as an option', function () {
    $bag = new ComponentAttributeBag(['title' => 'Hello']);
    Malevich::recipe($bag)->base('title', 'font-semibold');

    expect((string) new Element($bag))->toBe('title="Hello"');
});
