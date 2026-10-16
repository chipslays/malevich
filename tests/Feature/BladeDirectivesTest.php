<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Malevich;
use Malevich\MalevichServiceProvider;

it('compiles option directives into recipe declarations', function (string $option) {
    expect(Blade::compileString("@{$option}(['a' => 'b'])"))
        ->toContain("Malevich::recipe(\$attributes ?? null))->option('{$option}', ['a' => 'b'])");
})->with(['variant', 'color', 'size']);

it('compiles @option, @base and @compound', function () {
    expect(Blade::compileString("@option('tone', ['a' => 'b'])"))->toContain("->option('tone', ['a' => 'b'])")
        ->and(Blade::compileString("@base('title', 'x')"))->toContain("->base('title', 'x')")
        ->and(Blade::compileString("@compound(['size' => 'sm'], 'x')"))->toContain("->compound(['size' => 'sm'], 'x')");
});

it('compiles @attributes with and without arguments', function () {
    expect(Blade::compileString('<b @attributes>'))->toContain('Malevich::attributes($attributes ?? null, get_defined_vars()); echo $__malevichAttributes->debugComment(); ?><b <?php echo $__malevichAttributes; ?>>')
        ->and(Blade::compileString("<b @attributes('icon')>"))->toContain("Malevich::attributes(\$attributes ?? null, get_defined_vars(), 'icon')");
});

it('declares a recipe on the bag when the template runs', function () {
    $bag = new ComponentAttributeBag;

    Blade::render("@php(\$attributes = \$bag)\n@color('icon', ['red' => 'text-red-500'])", ['bag' => $bag]);

    expect(Malevich::recipe($bag)->optionsFor('icon'))->toBe(['color' => ['red' => 'text-red-500']]);
});

it('registers additional options from config', function () {
    config(['malevich.options' => ['radius']]);
    (new MalevichServiceProvider(app()))->boot();

    expect(Blade::compileString("@radius(['full' => 'rounded-full'])"))->toContain("->option('radius'")
        ->and(ComponentAttributeBag::hasMacro('radius'))->toBeTrue();
});
