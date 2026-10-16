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
        .str_replace(' @@', '<?php echo $__malevichAttributes->inTag(); ?>', $tag);
}

it('prints no stray space when the element has no attributes', function () {
    expect(Blade::render('<x-compound-targets size="lg">Go</x-compound-targets>', deleteCachedView: true))
        ->not->toContain('<span >')
        ->and(HtmlTagCompiler::compile("<div\n    @attributes('x')>"))->toBe(resolved("'x'", '<div @@>'));
});

it('resolves the element in front of the tag', function () {
    expect(HtmlTagCompiler::compile("<div @attributes('x')></div>"))->toBe(resolved("'x'", '<div @@></div>'))
        ->and(HtmlTagCompiler::compile('<div :class="a" data-class="b" @attributes></div>'))->toBe(resolved('', '<div :class="a" data-class="b" @@></div>'));
});

it('moves the class into class: and keeps existing arguments', function () {
    expect(HtmlTagCompiler::compile("<span @attributes('chip') class=\"a {{ \$b }}\">"))
        ->toBe(resolved("'chip', class: 'a '.e(\$b)", '<span @@>'))
        ->and(HtmlTagCompiler::compile('<b class="x" @attributes>'))
        ->toBe(resolved("class: 'x'", '<b @@>'))
        ->and(HtmlTagCompiler::compile("<div @attributes('a', merge: ['id' => 'x']) class=\"b\">"))
        ->toBe(resolved("'a', merge: ['id' => 'x'], class: 'b'", '<div @@>'));
});

it('treats the class inside the component like @base: the options go on top', function () {
    expect($this->render('<x-inline-base />'))->toBe('<div class="px-2 rounded px-4 m-1"></div>')
        ->and($this->render('<x-inline-base size="sm" class="mt-2" />'))->toBe('<div class="px-2 rounded px-1 m-1 mt-2"></div>');
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

it('refuses a class next to @attributes that already has class:', function () {
    HtmlTagCompiler::compile("<div @attributes(class: 'a') class=\"b\">");
})->throws(InvalidArgumentException::class, 'class:');

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
    expect(Blade::compileString('<b class="x" @attributes>'))->toContain("Malevich::attributes(\$attributes ?? null, get_defined_vars(), class: 'x')");
});

it('moves an unquoted class into class:', function () {
    expect(HtmlTagCompiler::compile('<b class=x @attributes>'))->toBe(resolved("class: 'x'", '<b @@>'));
});
