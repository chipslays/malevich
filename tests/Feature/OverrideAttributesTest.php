<?php

use Illuminate\View\ComponentAttributeBag;
use Malevich\Element;

it('wins over attributes passed to the component', function () {
    expect($this->render('<x-override role="alert" title="Hello" />'))
        ->toContain('role="status"')
        ->not->toContain('role="alert"')
        ->not->toContain('title=');
});

it('leaves an attribute as the caller set it when the override is null', function () {
    expect($this->render('<x-override aria-busy="false" />'))->toContain('aria-busy="false"');
    expect($this->render('<x-override busy aria-busy="false" />'))->toContain('aria-busy="true"')->not->toContain('aria-busy="false"');
});

it('escapes override values like merge values', function () {
    expect($this->render('<x-override />'))->toContain('data-note="&lt;b&gt;"')->not->toContain('<b>');
});

it('keeps classes out of override', function () {
    expect(fn () => (new Element(new ComponentAttributeBag))->override(['class' => 'x']))
        ->toThrow(InvalidArgumentException::class, 'cannot replace classes');
});

it('keeps the classes of the element', function () {
    expect($this->render('<x-override class="extra" />'))->toContain('class="root extra"');
});
