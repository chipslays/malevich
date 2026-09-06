<?php

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Malevich\Selector;
use Malevich\Support\DirectiveRegistry;

it('defaults to the configured default target', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500');
});

it('for() switches the target without mutating the original selector', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $base = new Selector($bag);
    $forIcon = $base->for('icon')->directive('color', 'red');

    expect($forIcon->toClasses())->toBe('text-red-500')
        ->and($base->directive('color', 'red')->toClasses())->toBe('');
});

it('applies a wildcard rule regardless of the selected choice', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'size', [
        '*' => 'inline-flex items-center',
        'sm' => 'text-sm',
    ]);

    $selector = (new Selector($bag))->directive('size', 'sm');

    expect($selector->toClasses())->toBe('inline-flex items-center text-sm');
});

it('applies only the wildcard rule when no matching choice was selected', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'size', [
        '*' => 'inline-flex',
        'sm' => 'text-sm',
    ]);

    $selector = new Selector($bag);

    expect($selector->toClasses())->toBe('inline-flex');
});

it('ignores a selected choice that is not present in the directive map', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->directive('color', 'blue');

    expect($selector->toClasses())->toBe('');
});

it('use() sets a target and merges choices in one call', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', 'btn', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->use('btn', ['color' => 'red']);

    expect($selector->toClasses())->toBe('text-red-500');
});

it('use() resolves per-target choice arrays against the current target', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))
        ->for('icon')
        ->use(['color' => ['icon' => 'red', 'label' => 'blue']]);

    expect($selector->toClasses())->toBe('text-red-500');
});

it('use() merges an explicit extra choices array on top', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);
    DirectiveRegistry::registerDirective($bag, 'size', ['lg' => 'text-lg']);

    $selector = (new Selector($bag))->use(['color' => 'red'], ['size' => 'lg']);

    expect($selector->toClasses())->toBe('text-red-500 text-lg');
});

it('directive() resolves a per-target value array against the current target', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))
        ->for('icon')
        ->directive('color', ['icon' => 'red', 'label' => 'blue']);

    expect($selector->toClasses())->toBe('text-red-500');
});

it('applies a preset registered for the current target', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);
    DirectiveRegistry::registerDirective($bag, 'size', ['lg' => 'text-lg']);
    DirectiveRegistry::registerPreset('danger', ['default' => ['color' => 'red', 'size' => 'lg']]);

    $selector = (new Selector($bag))->preset('danger');

    expect($selector->toClasses())->toBe('text-red-500 text-lg');
});

it('falls back to the flat preset shape when no per-target key matches', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);
    DirectiveRegistry::registerPreset('danger', ['color' => 'red']);

    $selector = (new Selector($bag))->preset('danger');

    expect($selector->toClasses())->toBe('text-red-500');
});

it('supports magic method calls as directive shortcuts', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'variant', ['solid' => 'bg-black']);

    $selector = (new Selector($bag))->variant('solid');

    expect($selector->toClasses())->toBe('bg-black');
});

it('a magic method call without an argument is a no-op', function () {
    $bag = new ComponentAttributeBag;

    $selector = (new Selector($bag))->somethingUndefined();

    expect($selector)->toBeInstanceOf(Selector::class)
        ->and($selector->toClasses())->toBe('');
});

it('merges the component own class attribute for the default target', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500 own-class');
});

it('does not leak the component class into a non-default target without an explicit slot', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->for('icon')->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500');
});

it('merges the slot class when a slot is explicitly provided for a non-default target', function () {
    $bag = new ComponentAttributeBag(['class' => 'ignored']);
    $slotBag = new ComponentAttributeBag(['class' => 'slot-class']);
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->for('icon')->slot($slotBag)->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500 slot-class');
});

it('extracts attributes from a ComponentSlot instance passed to slot()', function () {
    $bag = new ComponentAttributeBag;
    $slotAttributes = new ComponentAttributeBag(['class' => 'slot-class']);
    $slot = new ComponentSlot('content', $slotAttributes->getAttributes());
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->for('icon')->slot($slot)->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500 slot-class');
});

it('ignores slot() when given a value it cannot recognise', function () {
    $bag = new ComponentAttributeBag(['class' => 'own-class']);
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    // On the default target, an unrecognised slot() falls back to the
    // component's own attributes rather than merging nothing.
    $selector = (new Selector($bag))->slot('not-an-attribute-bag')->directive('color', 'red');

    expect($selector->toClasses())->toBe('text-red-500 own-class');
});

it('toHtml renders the resolved class together with passthrough attributes', function () {
    $bag = new ComponentAttributeBag(['id' => 'box', 'class' => 'own-class']);
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    $html = (string) (new Selector($bag))->directive('color', 'red');

    expect($html)->toContain('id="box"')
        ->and($html)->toContain('class="text-red-500 own-class"');
});

it('toHtml renders only the resolved class when there are no own attributes to merge', function () {
    $bag = new ComponentAttributeBag(['id' => 'box']);
    DirectiveRegistry::registerDirective($bag, 'color', 'icon', ['red' => 'text-red-500']);

    $html = (string) (new Selector($bag))->for('icon')->directive('color', 'red');

    expect($html)->toContain('class="text-red-500"')
        ->and($html)->not->toContain('id="box"');
});

it('__toString delegates to toHtml', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);

    $selector = (new Selector($bag))->directive('color', 'red');

    expect($selector->__toString())->toBe($selector->toHtml());
});
