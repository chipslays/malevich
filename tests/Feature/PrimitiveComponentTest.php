<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Selector;
use Malevich\Support\DirectiveRegistry;

it('renders as a div by default and passes through attributes', function () {
    $html = Blade::render('<x-ui::primitive id="box">Hello</x-ui::primitive>');

    expect($html)->toContain('<div')
        ->and($html)->toContain('id="box"')
        ->and($html)->toContain('Hello')
        ->and($html)->toContain('</div>');
});

it('renders a custom tag via the as prop', function () {
    $html = Blade::render('<x-ui::primitive as="section">Hi</x-ui::primitive>');

    expect($html)->toContain('<section')
        ->and($html)->toContain('</section>');
});

it('accepts a plain string as the class prop', function () {
    $html = Blade::render('<x-ui::primitive class="foo bar">Hi</x-ui::primitive>');

    expect($html)->toContain('class="foo bar"');
});

it('resolves an empty Selector instance passed as the class prop without error', function () {
    $html = Blade::render(
        '<x-ui::primitive :class="$selector">Hi</x-ui::primitive>',
        ['selector' => new Selector(new ComponentAttributeBag)],
    );

    expect($html)->toContain('<div')
        ->and($html)->toContain('Hi');
});

it('resolves a Selector with directives applied through the class prop', function () {
    $bag = new ComponentAttributeBag;
    DirectiveRegistry::registerDirective($bag, 'color', ['red' => 'text-red-500']);
    $selector = (new Selector($bag))->directive('color', 'red');

    $html = Blade::render(
        '<x-ui::primitive :class="$selector">Hi</x-ui::primitive>',
        ['selector' => $selector],
    );

    expect($html)->toContain('class="text-red-500"');
});

it('does not leak the class or as props as plain html attributes', function () {
    $html = Blade::render('<x-ui::primitive class="foo" as="span" id="x">Hi</x-ui::primitive>');

    expect($html)->toContain('<span')
        ->and($html)->not->toContain('as="span"')
        ->and($html)->toContain('id="x"');
});
