<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Element;
use Malevich\Malevich;
use Malevich\MalevichServiceProvider;
use Malevich\Recipe;

it('compiles @defaults into a recipe declaration', function () {
    expect(Blade::compileString("@defaults(['size' => 'md'])"))
        ->toContain("->defaults(['size' => 'md'])");
});

it('leaves @defaults without parentheses alone', function () {
    expect(Blade::compileString('@defaults ring-width;'))->toBe('@defaults ring-width;');
});

it('uses the defaults when nothing else picks a value', function () {
    expect($this->render('<x-defaulted>Go</x-defaulted>'))
        ->toBe('<button class="bg-black h-10 shadow"><svg class="size-3"></svg>Go</button>');
});

it('lets a preset win over the defaults', function () {
    expect($this->render('<x-defaulted preset="ghost">Go</x-defaulted>'))
        ->toBe('<button class="bg-transparent h-10"><svg class="size-3"></svg>Go</button>');
});

it('lets an attribute on the tag win over the defaults, on every target', function () {
    expect($this->render('<x-defaulted variant="ghost" size="md">Go</x-defaulted>'))
        ->toBe('<button class="bg-transparent h-10"><svg class="size-4"></svg>Go</button>');
});

it('never renders the axes as HTML attributes', function () {
    expect($this->render('<x-defaulted variant="ghost" preset="ghost">Go</x-defaulted>'))
        ->not->toContain('variant=', 'preset=');
});

it('keeps the old trap of a non-null @props default shadowing a preset', function () {
    // Documented behaviour: a @props default counts as "set". @defaults is the fix.
    expect($this->render('<x-props-default preset="ghost">Go</x-props-default>'))
        ->toBe('<button class="bg-black">Go</button>');
});

it('ranks the template scope above the defaults', function () {
    $recipe = (new Recipe)
        ->axis('variant', ['primary' => 'a', 'ghost' => 'b'])
        ->defaults(['variant' => 'primary']);

    $element = new Element(new ComponentAttributeBag, null, ['variant' => 'ghost'], $recipe);

    expect($element->toClasses())->toBe('b')
        ->and((new Element(new ComponentAttributeBag, null, ['variant' => null], $recipe))->toClasses())->toBe('a');
});

it('ranks an explicit call above everything', function () {
    $recipe = (new Recipe)
        ->axis('variant', ['primary' => 'a', 'ghost' => 'b'])
        ->defaults(['variant' => 'primary']);

    expect((new Element(new ComponentAttributeBag, recipe: $recipe))->variant('ghost')->toClasses())->toBe('b');
});

it('accepts a per-target value map, like a tag attribute', function () {
    $recipe = (new Recipe)
        ->axis('size', ['sm' => 'h-8', 'md' => 'h-10'])
        ->axis('size', 'icon', ['sm' => 'size-3', 'md' => 'size-4'])
        ->defaults(['size' => ['default' => 'md', 'icon' => 'sm']]);

    $element = new Element(new ComponentAttributeBag, recipe: $recipe);

    expect($element->toClasses())->toBe('h-10')
        ->and($element->for('icon')->toClasses())->toBe('size-3');
});

it('merges several declarations, target-specific values winning', function () {
    $recipe = (new Recipe)
        ->defaults(['variant' => 'primary', 'size' => 'md'])
        ->defaults(['size' => 'lg'])
        ->defaults('icon', ['size' => 'sm']);

    expect($recipe->defaultsFor('default'))->toBe(['variant' => 'primary', 'size' => 'lg'])
        ->and($recipe->defaultsFor('icon'))->toBe(['variant' => 'primary', 'size' => 'sm']);
});

it('takes part in @compound conditions', function () {
    $recipe = (new Recipe)
        ->compound(['loading' => true], 'cursor-wait')
        ->defaults(['loading' => true]);

    expect((new Element(new ComponentAttributeBag, recipe: $recipe))->toClasses())->toBe('cursor-wait');
});

it('rejects malformed declarations', function (array $arguments) {
    (new Recipe)->defaults(...$arguments);
})->with([
    'no arguments' => [[]],
    'not an array' => [['primary']],
    'too many' => [['icon', [], []]],
])->throws(InvalidArgumentException::class, '@defaults expects');

it('reserves the @defaults name', function () {
    config(['malevich.directives' => ['defaults']]);
    (new MalevichServiceProvider(app()))->boot();
})->throws(InvalidArgumentException::class, 'is already a Malevich directive');

it('works through the attribute bag macros too', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)
        ->axis('color', ['gray' => 'text-gray-500', 'red' => 'text-red-500'])
        ->defaults(['color' => 'gray']);

    expect($bag->color(null)->toClasses())->toBe('text-gray-500')
        ->and($bag->color('red')->toClasses())->toBe('text-red-500');
});
