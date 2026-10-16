<?php

use Malevich\Support\ConstantExpression;

it('reads literals', function (string $source, mixed $value) {
    expect(ConstantExpression::evaluate($source))->toBe($value);
})->with([
    'single quotes' => ["'a\'b'", "a'b"],
    'double quotes' => ['"a\\"b"', 'a"b'],
    'numbers' => ['[1, -2, 1.5]', [1, -2, 1.5]],
    'constants' => ['[true, FALSE, null]', [true, false, null]],
    'nested arrays' => ["['sm' => ['icon' => 'x'], 'lg' => 'y',]", ['sm' => ['icon' => 'x'], 'lg' => 'y']],
    'long arrays' => ["array('a' => array(1))", ['a' => [1]]],
    'boolean keys' => ["[true => 'on']", [1 => 'on']],
    'concatenation' => ["'a '.'b'", 'a b'],
    'comments' => ["[/* x */ 'a', // y\n 'b']", ['a', 'b']],
]);

it('refuses anything that is not a literal', function (string $source) {
    ConstantExpression::evaluate($source);
})->with([
    'variable' => ['$sizes'],
    'function call' => ['sizes()'],
    'constant' => ['SIZES'],
    'class constant' => ['Size::Large'],
    'interpolation' => ['"a $b"'],
    'array with a variable' => ["['sm' => \$x]"],
    'trailing code' => ["'a' + 1"],
    'unclosed array' => ["['a'"],
])->throws(InvalidArgumentException::class);

it('splits directive arguments at top-level commas only', function () {
    expect(ConstantExpression::split("'icon', ['a' => fn (\$x, \$y) => [1, 2]], \$c"))
        ->toBe(["'icon'", "['a' => fn (\$x, \$y) => [1, 2]]", '$c'])
        ->and(ConstantExpression::split(''))->toBe([]);
});
