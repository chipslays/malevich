<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Support\HtmlTagCompiler;

it('merges class="..." next to @attributes into one class attribute', function () {
    $html = $this->render('<x-plain-tags />');

    expect($html)
        ->toContain('<span class="base-chip from-tag dyn"></span>')
        ->toContain('<span class="base-last before"></span>')
        ->toContain('<i class="base-chip single"></i>')
        ->not->toContain('class="base-chip" class=');
});

it('escapes interpolated values', function () {
    expect($this->render('<x-plain-tags extra="a&quot;b" />'))->not->toContain('class="base-chip from-tag a"b"');
});

it('moves the class into merge: and keeps existing arguments', function () {
    expect(HtmlTagCompiler::compile("<span @attributes('chip') class=\"a {{ \$b }}\">"))
        ->toBe("<span @attributes('chip', merge: ['class' => 'a '.(string) (\$b)])>")
        ->and(HtmlTagCompiler::compile('<b class="x" @attributes>'))
        ->toBe("<b @attributes(merge: ['class' => 'x'])>");
});

it('leaves everything else alone', function (string $template) {
    expect(HtmlTagCompiler::compile($template))->toBe($template);
})->with([
    'no @attributes' => ['<div class="a"></div>'],
    'no class' => ["<div @attributes('x')></div>"],
    '<x-...> tags' => ['<x-foo class="a" @attributes />'],
    'other attributes' => ['<div :class="a" data-class="b" @attributes></div>'],
    'quoted @attributes' => ['<div title="press @attributes" class="a"></div>'],
    'blade comment' => ['{{-- <div class="{!! $a !!}" @attributes> --}}'],
    'verbatim' => ['@verbatim <div class="a" @attributes></div> @endverbatim'],
]);

it('refuses a class next to @attributes that already has merge:', function () {
    HtmlTagCompiler::compile("<div @attributes('a', merge: ['id' => 'x']) class=\"b\">");
})->throws(InvalidArgumentException::class, 'merge:');

it('refuses {!! !!} inside the class', function () {
    HtmlTagCompiler::compile('<div @attributes class="{!! $a !!}">');
})->throws(InvalidArgumentException::class, '{!! !!}');

it('refuses a Blade directive inside the class', function () {
    HtmlTagCompiler::compile('<div @attributes class="a @if($b) c @endif">');
})->throws(InvalidArgumentException::class, 'Blade directive');

it('refuses two class attributes', function () {
    HtmlTagCompiler::compile('<div @attributes class="a" class="b">');
})->throws(InvalidArgumentException::class, 'more than one class');

it('compiles the rewritten tag into one @attributes call', function () {
    expect(Blade::compileString('<b class="x" @attributes>'))->toContain("Malevich::attributes(\$attributes ?? null, get_defined_vars(), merge: ['class' => 'x'])");
});

it('moves an unquoted class into merge:', function () {
    expect(HtmlTagCompiler::compile('<b class=x @attributes>'))->toBe("<b @attributes(merge: ['class' => 'x'])>");
});
