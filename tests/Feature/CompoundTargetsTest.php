<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Malevich;
use Malevich\Recipe;

it('styles several elements for one combination', function () {
    expect($this->render('<x-compound-targets size="sm" variant="ghost">Go</x-compound-targets>'))
        ->toBe('<button class="btn h-8 px-2"><i class="icon size-3"></i><span class="text-xs">Go</span></button>');
});

it('accepts conditional classes for an element', function () {
    expect($this->render('<x-compound-targets size="sm" variant="ghost" muted>Go</x-compound-targets>'))
        ->toContain('<i class="icon size-3 opacity-70"></i>');
});

it('adds nothing when the combination does not match', function () {
    expect($this->render('<x-compound-targets size="sm">Go</x-compound-targets>'))
        ->toBe('<button class="btn h-8 bg-black"><i class="icon"></i><span>Go</span></button>');
});

it('compiles to a call with the named argument', function () {
    expect(Blade::compileString("@compound(['size' => 'sm'], targets: ['icon' => 'size-3'])"))
        ->toContain("->compound(['size' => 'sm'], targets: ['icon' => 'size-3'])");
});

it('refuses targets that are not keyed by element', function (Closure $declare) {
    $declare(new Recipe);
})->with([
    'a list' => [fn (Recipe $recipe) => $recipe->compound(['size' => 'sm'], targets: ['size-3', 'px-2'])],
    'not an array' => [fn (Recipe $recipe) => $recipe->compound(['size' => 'sm'], targets: 'size-3')],
    'empty' => [fn (Recipe $recipe) => $recipe->compound(['size' => 'sm'], targets: [])],
    'with classes too' => [fn (Recipe $recipe) => $recipe->compound(['size' => 'sm'], 'px-2', targets: ['icon' => 'size-3'])],
    'with a target too' => [fn (Recipe $recipe) => $recipe->compound('icon', ['size' => 'sm'], targets: ['icon' => 'size-3'])],
])->throws(InvalidArgumentException::class, 'targets: expects');

it('counts the values it tests as known in strict mode', function () {
    Malevich::strict();

    expect($this->render('<x-compound-targets size="sm" variant="ghost">Go</x-compound-targets>'))->toContain('px-2');
});
