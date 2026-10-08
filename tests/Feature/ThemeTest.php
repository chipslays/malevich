<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Malevich;
use Malevich\Recipe;

it('compiles @theme into a recipe declaration', function () {
    expect(Blade::compileString("@theme('variant', ['a' => ['default' => 'x']])"))
        ->toContain("->theme('variant', ['a' => ['default' => 'x']])");
});

it('styles every element of the chosen value from one declaration', function () {
    $teal = $this->render('<x-themed>Hi</x-themed>');

    expect($teal)
        ->toContain('<div class="flex bg-teal">')
        ->toContain('<span class="font-medium text-teal-100">Hi</span>')
        ->toContain('<i class="glow-teal"></i>')
        ->not->toContain('no glow');

    $graphite = $this->render('<x-themed variant="graphite">Hi</x-themed>');

    expect($graphite)
        ->toContain('<div class="flex bg-graphite">')
        ->toContain('<span class="font-medium text-white">Hi</span>')
        ->toContain('<b>no glow</b>')
        ->not->toContain('<i');
});

it('lets @hasUi guard a part that exists only in some variants', function () {
    expect($this->render('<x-themed variant="graphite">Hi</x-themed>'))->not->toContain('<i')
        ->and($this->render('<x-themed variant="teal">Hi</x-themed>'))->toContain('<i class="glow-teal">');
});

it('fills the same slots as one declaration per target', function () {
    $recipe = (new Recipe)->theme('variant', [
        'teal' => ['default' => 'a', 'title' => 'b'],
        'dark' => ['title' => 'c'],
    ]);

    expect($recipe->axesFor('default'))->toBe(['variant' => ['teal' => 'a']])
        ->and($recipe->axesFor('title'))->toBe(['variant' => ['teal' => 'b', 'dark' => 'c']]);
});

it('treats a plain value as classes of the root element', function () {
    $recipe = (new Recipe)->theme('variant', ['teal' => 'a b', 'dark' => ['c', 'd']]);

    expect($recipe->axesFor('default'))->toBe(['variant' => ['teal' => 'a b', 'dark' => ['c', 'd']]]);
});

it('merges several declarations of the same axis and value', function () {
    $recipe = (new Recipe)
        ->theme('variant', ['teal' => ['title' => ['a', 'hidden' => false]]])
        ->theme('variant', ['teal' => ['title' => 'b'], 'dark' => ['title' => 'c']]);

    expect($recipe->axesFor('title'))->toBe(['variant' => ['teal' => 'a b', 'dark' => 'c']]);
});

it('keeps a catch-all value working', function () {
    $recipe = (new Recipe)->theme('variant', ['*' => ['title' => 'always']]);

    expect($recipe->axesFor('title'))->toBe(['variant' => ['*' => 'always']]);
});

it('is picked up as an axis, so the value never renders as an attribute', function () {
    expect($this->render('<x-themed variant="graphite">Hi</x-themed>'))->not->toContain('variant=');
});

it('declares a theme on the bag when the template runs', function () {
    $bag = new ComponentAttributeBag;

    Blade::render("@php(\$attributes = \$bag)\n@theme('size', ['sm' => ['icon' => 'size-3']])", ['bag' => $bag]);

    expect(Malevich::recipe($bag)->axesFor('icon'))->toBe(['size' => ['sm' => 'size-3']]);
});

it('adds to an earlier @variant, but is replaced by a later one', function () {
    $recipe = (new Recipe)
        ->axis('variant', ['*' => 'base', 'teal' => 'a'])
        ->theme('variant', ['teal' => ['default' => 'b']]);

    expect($recipe->axesFor('default'))->toBe(['variant' => ['*' => 'base', 'teal' => 'a b']]);

    $recipe->axis('variant', ['teal' => 'c']);

    expect($recipe->axesFor('default'))->toBe(['variant' => ['teal' => 'c']]);
});
