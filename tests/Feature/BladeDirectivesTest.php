<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Support\DirectiveRegistry;

it('compiles @directive into a DirectiveRegistry::registerDirective call', function () {
    $compiled = Blade::compileString("@directive('color', ['red' => 'text-red-500'])");

    expect($compiled)->toContain('\\Malevich\\Support\\DirectiveRegistry::registerDirective($attributes,');
});

it('compiles @preset into a DirectiveRegistry::registerPreset call', function () {
    $compiled = Blade::compileString("@preset('danger', ['color' => 'red'])");

    expect($compiled)->toContain('\\Malevich\\Support\\DirectiveRegistry::registerPreset(');
});

it('compiles a configured dynamic directive like @color with its name baked in', function () {
    $compiled = Blade::compileString("@color(['red' => 'text-red-500'])");

    expect($compiled)->toContain("DirectiveRegistry::registerDirective(\$attributes, 'color',");
});

it('compiles @variant and @size as well, per the default config', function () {
    expect(Blade::compileString("@variant(['solid' => 'bg-black'])"))
        ->toContain("DirectiveRegistry::registerDirective(\$attributes, 'variant',")
        ->and(Blade::compileString("@size(['sm' => 'text-sm'])"))
        ->toContain("DirectiveRegistry::registerDirective(\$attributes, 'size',");
});

it('registers a directive end-to-end when the compiled template runs', function () {
    $bag = new ComponentAttributeBag;

    Blade::render(
        "@php(\$attributes = \$bag)\n@color(['red' => 'text-red-500'])",
        ['bag' => $bag],
    );

    expect(DirectiveRegistry::getDirectives($bag, 'default'))
        ->toBe(['color' => ['red' => 'text-red-500']]);
});

it('registers a preset end-to-end when the compiled template runs', function () {
    Blade::render("@preset('danger', ['color' => 'red', 'size' => 'lg'])");

    expect(DirectiveRegistry::getPreset('danger'))->toBe(['color' => 'red', 'size' => 'lg']);
});
