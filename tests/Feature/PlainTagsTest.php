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

/**
 * What a plain tag compiles to: the element resolved in front of the tag
 * (with its debug comment), then printed inside it.
 */
function resolved(string $arguments, string $tag): string
{
    $arguments = $arguments === '' ? '' : ", {$arguments}";

    return "<?php \$__malevichAttributes = \\Malevich\\Malevich::attributes(\$attributes ?? null, get_defined_vars(){$arguments}); echo \$__malevichAttributes->debugComment(); ?>"
        .str_replace('@@', '<?php echo $__malevichAttributes; ?>', $tag);
}

it('resolves the element in front of the tag', function () {
    expect(HtmlTagCompiler::compile("<div @attributes('x')></div>"))->toBe(resolved("'x'", '<div @@></div>'))
        ->and(HtmlTagCompiler::compile('<div :class="a" data-class="b" @attributes></div>'))->toBe(resolved('', '<div :class="a" data-class="b" @@></div>'));
});

it('moves the class into merge: and keeps existing arguments', function () {
    expect(HtmlTagCompiler::compile("<span @attributes('chip') class=\"a {{ \$b }}\">"))
        ->toBe(resolved("'chip', merge: ['class' => 'a '.(string) (\$b)]", '<span @@>'))
        ->and(HtmlTagCompiler::compile('<b class="x" @attributes>'))
        ->toBe(resolved("merge: ['class' => 'x']", '<b @@>'));
});

it('leaves everything else alone', function (string $template) {
    expect(HtmlTagCompiler::compile($template))->toBe($template);
})->with([
    'no @attributes' => ['<div class="a"></div>'],
    '<x-...> tags' => ['<x-foo class="a" @attributes />'],
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
    expect(HtmlTagCompiler::compile('<b class=x @attributes>'))->toBe(resolved("merge: ['class' => 'x']", '<b @@>'));
});
