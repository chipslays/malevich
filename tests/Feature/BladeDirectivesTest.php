<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Malevich;
use Malevich\MalevichServiceProvider;

it('compiles axis directives into recipe declarations', function (string $directive) {
    expect(Blade::compileString("@{$directive}(['a' => 'b'])"))
        ->toContain("Malevich::recipe(\$attributes))->axis('{$directive}', ['a' => 'b'])");
})->with(['variant', 'color', 'size']);

it('compiles @directive, @compound and @preset', function () {
    expect(Blade::compileString("@directive('tone', ['a' => 'b'])"))->toContain("->axis('tone', ['a' => 'b'])")
        ->and(Blade::compileString("@base('title', 'x')"))->toContain("->base('title', 'x')")
        ->and(Blade::compileString("@compound(['size' => 'sm'], 'x')"))->toContain("->compound(['size' => 'sm'], 'x')")
        ->and(Blade::compileString("@preset('p', [])"))->toContain("->preset('p', [])");
});

it('compiles @ui with and without arguments', function () {
    expect(Blade::compileString('<b @ui>'))->toContain('Malevich::ui($attributes, get_defined_vars()); ?>>')
        ->and(Blade::compileString("<b @ui('icon')>"))->toContain("Malevich::ui(\$attributes, get_defined_vars(), 'icon')");
});

it('declares a recipe on the bag when the template runs', function () {
    $bag = new ComponentAttributeBag;

    Blade::render("@php(\$attributes = \$bag)\n@color('icon', ['red' => 'text-red-500'])", ['bag' => $bag]);

    expect(Malevich::recipe($bag)->axesFor('icon'))->toBe(['color' => ['red' => 'text-red-500']]);
});

it('registers additional axes from config', function () {
    config(['malevich.directives' => ['radius']]);
    (new MalevichServiceProvider(app()))->boot();

    expect(Blade::compileString("@radius(['full' => 'rounded-full'])"))->toContain("->axis('radius'")
        ->and(ComponentAttributeBag::hasMacro('radius'))->toBeTrue();
});
