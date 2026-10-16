<?php

use Illuminate\View\ComponentAttributeBag;
use Malevich\Element;
use Malevich\Malevich;

function switchable(array $attributes = []): Element
{
    $bag = new ComponentAttributeBag($attributes);

    Malevich::recipe($bag)
        ->base('btn')
        ->option('variant', ['primary' => 'bg-black', 'danger' => 'bg-red'])
        ->option('size', ['md' => 'h-10', 'lg' => 'h-12'])
        ->option('size', 'icon', ['xl' => 'size-6'])
        ->compound(['variant' => 'danger', 'size' => 'lg'], 'shadow')
        ->compound(['loading' => true], 'cursor-wait');

    return new Element($bag);
}

it('resolves the classes of every value of an option', function () {
    expect(switchable(['class' => 'mt-2'])->size('lg')->classesFor('variant'))->toBe([
        'primary' => 'btn bg-black h-12 mt-2',
        'danger' => 'btn bg-red h-12 shadow mt-2',
    ]);
});

it('includes values known from other elements and from @compound', function () {
    expect(switchable()->classesFor('size'))->toBe(['md' => 'btn h-10', 'lg' => 'btn h-12', 'xl' => 'btn'])
        ->and(switchable()->classesFor('loading'))->toBe(['true' => 'btn cursor-wait']);
});

it('takes the values to resolve', function () {
    expect(switchable()->classesFor('variant', ['danger', 'unknown']))->toBe([
        'danger' => 'btn bg-red',
        'unknown' => 'btn',
    ]);
});

it('works for inner elements', function () {
    expect(switchable()->for('icon')->classesFor('size', ['md', 'xl']))->toBe(['md' => '', 'xl' => 'size-6']);
});

it('is available on $attributes for Alpine', function () {
    $html = $this->render('<x-switchable size="lg" />');

    expect($html)->toContain('x-data="{ classes: JSON.parse(')
        ->and(json_decode(json_decode('"'.str($html)->between("JSON.parse('", "')").'"'), true))->toBe([
            'primary' => 'btn bg-black h-12',
            'danger' => 'btn bg-red h-12 shadow',
        ]);
});

it('can be called on $attributes directly', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('variant', ['primary' => 'a', 'danger' => 'b']);

    expect($bag->classesFor('variant'))->toBe(['primary' => 'a', 'danger' => 'b']);
});
