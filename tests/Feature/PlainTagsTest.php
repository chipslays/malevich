<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Support\HtmlTagCompiler;

it('merges class="..." next to @ui into one class attribute', function () {
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
    expect(HtmlTagCompiler::compile("<span @ui('chip') class=\"a {{ \$b }}\">"))
        ->toBe("<span @ui('chip', merge: ['class' => 'a '.(string) (\$b)])>")
        ->and(HtmlTagCompiler::compile('<b class="x" @ui>'))
        ->toBe("<b @ui(merge: ['class' => 'x'])>");
});

it('leaves everything else alone', function (string $template) {
    expect(HtmlTagCompiler::compile($template))->toBe($template);
})->with([
    'no @ui' => ['<div class="a"></div>'],
    'no class' => ["<div @ui('x')></div>"],
    '<x-...> tags' => ['<x-foo class="a" @ui />'],
    'other attributes' => ['<div :class="a" data-class="b" @ui></div>'],
    'quoted @ui' => ['<div title="press @ui" class="a"></div>'],
    'blade comment' => ['{{-- <div class="{!! $a !!}" @ui> --}}'],
    'verbatim' => ['@verbatim <div class="a" @ui></div> @endverbatim'],
]);

it('refuses a class next to @ui that already has merge:', function () {
    HtmlTagCompiler::compile("<div @ui('a', merge: ['id' => 'x']) class=\"b\">");
})->throws(InvalidArgumentException::class, 'merge:');

it('refuses {!! !!} inside the class', function () {
    HtmlTagCompiler::compile('<div @ui class="{!! $a !!}">');
})->throws(InvalidArgumentException::class, '{!! !!}');

it('refuses a Blade directive inside the class', function () {
    HtmlTagCompiler::compile('<div @ui class="a @if($b) c @endif">');
})->throws(InvalidArgumentException::class, 'Blade directive');

it('refuses two class attributes', function () {
    HtmlTagCompiler::compile('<div @ui class="a" class="b">');
})->throws(InvalidArgumentException::class, 'more than one class');

it('compiles the rewritten tag into one @ui call', function () {
    expect(Blade::compileString('<b class="x" @ui>'))->toContain("Malevich::ui(\$attributes, get_defined_vars(), merge: ['class' => 'x'])");
});
