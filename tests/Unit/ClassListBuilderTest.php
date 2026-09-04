<?php

use Malevich\Support\ClassListBuilder;

it('is empty by default', function () {
    expect((string) new ClassListBuilder)->toBe('');
});

it('is immutable: add() returns a new instance and leaves the original untouched', function () {
    $original = new ClassListBuilder;
    $withClass = $original->add('foo');

    expect($original)->not->toBe($withClass)
        ->and((string) $original)->toBe('')
        ->and((string) $withClass)->toBe('foo');
});

it('accepts a plain class string', function () {
    $ClassListBuilder = (new ClassListBuilder)->add('foo bar');

    expect((string) $ClassListBuilder)->toBe('foo bar');
});

it('accepts a conditional class array, à la Arr::toCssClasses', function () {
    $ClassListBuilder = (new ClassListBuilder)->add([
        'always-on' => true,
        'never-on' => false,
        'also-always-on',
    ]);

    expect((string) $ClassListBuilder)->toBe('always-on also-always-on');
});

it('merges several add() calls together', function () {
    $ClassListBuilder = (new ClassListBuilder)
        ->add('foo')
        ->add('bar baz');

    expect((string) $ClassListBuilder)->toBe('foo bar baz');
});

it('deduplicates classes while preserving the first occurrence order', function () {
    $ClassListBuilder = (new ClassListBuilder)
        ->add('foo bar')
        ->add('bar baz foo');

    expect((string) $ClassListBuilder)->toBe('foo bar baz');
});

it('ignores an add() call that compiles to an empty string', function () {
    $ClassListBuilder = (new ClassListBuilder)
        ->add([])
        ->add(['hidden' => false])
        ->add('foo');

    expect((string) $ClassListBuilder)->toBe('foo');
});

it('treats null as "nothing to add"', function () {
    expect((string) (new ClassListBuilder)->add(null))->toBe('');
});

it('collapses accidental double spaces from the source string', function () {
    $ClassListBuilder = (new ClassListBuilder)->add('foo   bar');

    expect((string) $ClassListBuilder)->toBe('foo bar');
});
