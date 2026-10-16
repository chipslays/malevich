<?php

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ViewException;
use Malevich\Element;
use Malevich\Exceptions\UnknownValueException;
use Malevich\Malevich;
use Malevich\Recipe;

enum StrictStatus: string
{
    case Paid = 'paid';
    case Overdue = 'overdue';
}

function strictElement(Recipe $recipe): Element
{
    return new Element(new ComponentAttributeBag, recipe: $recipe);
}

it('is off by default: an unknown value renders no classes', function () {
    expect(Malevich::isStrict())->toBeFalse()
        ->and($this->render('<x-strict-button size="xl">Go</x-strict-button>'))
        ->toBe('<button class="bg-black"><svg ></svg>Go</button>');
});

it('throws on an unknown value, listing the valid ones', function () {
    Malevich::strict();

    $this->render('<x-strict-button size="xl">Go</x-strict-button>');
})->throws(ViewException::class, 'size [xl] is not defined; expected one of: sm, md.');

it('throws a dedicated exception', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('size', ['sm' => 'h-8', 'lg' => 'h-12']);

    expect(fn () => strictElement($recipe)->size('xl')->toClasses())
        ->toThrow(UnknownValueException::class, 'Malevich: size [xl] is not defined; expected one of: sm, lg.');
});

it('names the element when it is not the root', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('size', 'icon', ['sm' => 'size-3']);

    strictElement($recipe)->for('icon')->size('xl')->toClasses();
})->throws(UnknownValueException::class, 'size [xl] is not defined (element [icon]); expected one of: sm.');

it('accepts every known value', function () {
    Malevich::strict();

    expect($this->render('<x-strict-button variant="ghost" size="sm">Go</x-strict-button>'))
        ->toBe('<button class="bg-transparent h-8"><svg class="size-3"></svg>Go</button>');
});

it('ignores null and the catch-all value', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('size', ['*' => 'flex', 'sm' => 'h-8']);

    expect(strictElement($recipe)->size(null)->toClasses())->toBe('flex')
        ->and(strictElement($recipe)->size('*')->toClasses())->toBe('flex');
});

it('ignores options without a class map on the element', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('size', ['sm' => 'h-8'])->base('icon', 'size-4');

    expect(strictElement($recipe)->for('icon')->size('xl')->toClasses())->toBe('size-4');
});

it('ignores a class map with only the catch-all key', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('size', 'p-4');

    expect(strictElement($recipe)->size('lg')->toClasses())->toBe('p-4');
});

it('accepts a value that only styles some of the elements', function () {
    Malevich::strict();

    expect($this->render('<x-cased variant="graphite">Hi</x-cased>'))->toContain('<b>no glow</b>');
});

it('accepts a value that only appears in a @compound condition', function () {
    Malevich::strict();

    $recipe = (new Recipe)
        ->option('variant', ['solid' => 'text-white'])
        ->compound(['variant' => ['soft', 'outline'], 'color' => 'red'], 'bg-red-100');

    expect(strictElement($recipe)->use(['variant' => 'soft', 'color' => 'red'])->toClasses())->toBe('bg-red-100');
});

it('accepts the missing side of a boolean map', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('checked', ['true' => 'bg-green-500']);

    expect(strictElement($recipe)->option('checked', false)->toClasses())->toBe('')
        ->and(strictElement($recipe)->option('checked', true)->toClasses())->toBe('bg-green-500');
});

it('looks enums up by their value', function () {
    Malevich::strict();

    $recipe = (new Recipe)->option('status', ['paid' => 'bg-green-100']);

    expect(strictElement($recipe)->option('status', StrictStatus::Paid)->toClasses())->toBe('bg-green-100')
        ->and(fn () => strictElement($recipe)->option('status', StrictStatus::Overdue)->toClasses())
        ->toThrow(UnknownValueException::class, 'status [overdue] is not defined; expected one of: paid.');
});

it('can be turned off again and is reset by flush()', function () {
    Malevich::strict();
    Malevich::strict(false);

    expect(Malevich::isStrict())->toBeFalse();

    Malevich::strict();
    Malevich::flush();

    expect(Malevich::isStrict())->toBeFalse();
});
