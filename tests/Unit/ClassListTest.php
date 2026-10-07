<?php

use Malevich\Support\ClassList;

it('is empty by default', function () {
    expect((string) new ClassList)->toBe('');
});

it('is immutable: add() returns a new instance and leaves the original untouched', function () {
    $original = new ClassList;
    $withClass = $original->add('foo');

    expect($original)->not->toBe($withClass)
        ->and((string) $original)->toBe('')
        ->and((string) $withClass)->toBe('foo');
});

it('accepts a plain class string', function () {
    $ClassList = (new ClassList)->add('foo bar');

    expect((string) $ClassList)->toBe('foo bar');
});

it('accepts a conditional class array, à la Arr::toCssClasses', function () {
    $ClassList = (new ClassList)->add([
        'always-on' => true,
        'never-on' => false,
        'also-always-on',
    ]);

    expect((string) $ClassList)->toBe('always-on also-always-on');
});

it('merges several add() calls together', function () {
    $ClassList = (new ClassList)
        ->add('foo')
        ->add('bar baz');

    expect((string) $ClassList)->toBe('foo bar baz');
});

it('deduplicates classes while preserving the first occurrence order', function () {
    $ClassList = (new ClassList)
        ->add('foo bar')
        ->add('bar baz foo');

    expect((string) $ClassList)->toBe('foo bar baz');
});

it('ignores an add() call that compiles to an empty string', function () {
    $ClassList = (new ClassList)
        ->add([])
        ->add(['hidden' => false])
        ->add('foo');

    expect((string) $ClassList)->toBe('foo');
});

it('treats null as "nothing to add"', function () {
    expect((string) (new ClassList)->add(null))->toBe('');
});

it('collapses accidental double spaces from the source string', function () {
    $ClassList = (new ClassList)->add('foo   bar');

    expect((string) $ClassList)->toBe('foo bar');
});
