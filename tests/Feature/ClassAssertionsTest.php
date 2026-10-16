<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\AnonymousComponent;
use Malevich\Testing\ClassAssertions;
use PHPUnit\Framework\ExpectationFailedException;

it('finds an element with all of the classes, in any order', function () {
    $this->blade('<x-button variant="ghost">Hi</x-button>')
        ->assertSeeClasses('btn-ghost btn')
        ->assertSeeClasses(['btn'])
        ->assertDontSeeClasses('btn-solid');
});

it('explains what it found when the classes are missing', function () {
    $this->blade('<x-button variant="ghost">Hi</x-button>')->assertSeeClasses('btn-solid');
})->throws(ExpectationFailedException::class, 'class="btn btn-ghost"');

it('fails when a class it must not see is there', function () {
    $this->blade('<x-button variant="ghost">Hi</x-button>')->assertDontSeeClasses('btn');
})->throws(ExpectationFailedException::class, 'no element has the classes [btn]');

it('needs all of the classes on one element', function () {
    expect(ClassAssertions::has('<i class="a"></i><b class="b"></b>', ['a', 'b']))->toBeFalse()
        ->and(ClassAssertions::has("<i class='b a c'></i>", ['a', 'b']))->toBeTrue();
});

it('decodes HTML entities in class attributes', function () {
    expect(ClassAssertions::has('<i class="[&amp;&gt;svg]:size-4"></i>', ['[&>svg]:size-4']))->toBeTrue();
});

it('works on responses and components', function () {
    Route::get('/malevich-classes', fn () => '<p class="x y">Hi</p>');

    $this->get('/malevich-classes')->assertSeeClasses('y x');
    $this->component(AnonymousComponent::class, ['view' => 'components.toggle', 'data' => ['checked' => true]])
        ->assertSeeClasses('bg-green-500');
});

it('adds toHaveClasses() to Pest', function () {
    expect('<div class="a b"></div>')->toHaveClasses(['b', 'a'])
        ->and(fn () => expect('<div class="a"></div>')->toHaveClasses('b'))->toThrow(ExpectationFailedException::class);
});
