<?php

use Illuminate\View\ViewException;
use Malevich\Support\ComponentTagCompiler;

it('renders a div by default and passes attributes through', function () {
    expect($this->render('<x-malevich::primitive class="p-2" id="x">Hi</x-malevich::primitive>'))
        ->toBe('<div class="p-2" id="x">Hi</div>');
});

it('renders any tag with "as"', function () {
    expect($this->render('<x-malevich::primitive as="section">Hi</x-malevich::primitive>'))
        ->toBe('<section>Hi</section>');
});

it('gives buttons type="button" unless another type is passed', function () {
    expect($this->render('<x-malevich::primitive as="button">Go</x-malevich::primitive>'))
        ->toBe('<button type="button">Go</button>')
        ->and($this->render('<x-malevich::primitive as="button" type="submit">Go</x-malevich::primitive>'))
        ->toBe('<button type="submit">Go</button>');
});

it('turns anything with an href into a link', function () {
    expect($this->render('<x-malevich::primitive href="/a">A</x-malevich::primitive>'))->toBe('<a href="/a">A</a>')
        ->and($this->render('<x-malevich::primitive as="button" href="/a">A</x-malevich::primitive>'))->toBe('<a href="/a">A</a>');
});

it('secures links opened in a new tab', function () {
    expect($this->render('<x-malevich::primitive href="/a" target="_blank">A</x-malevich::primitive>'))
        ->toBe('<a href="/a" target="_blank" rel="noopener noreferrer">A</a>');
});

it('disables natively where possible', function () {
    expect($this->render('<x-malevich::primitive as="button" disabled>Go</x-malevich::primitive>'))
        ->toBe('<button type="button" data-disabled="" disabled="disabled">Go</button>');
});

it('disables links with aria and drops the href', function () {
    expect($this->render('<x-malevich::primitive href="/a" disabled>A</x-malevich::primitive>'))
        ->toBe('<a data-disabled="" aria-disabled="true" tabindex="-1">A</a>');
});

it('renders void elements without content', function () {
    expect($this->render('<x-malevich::primitive as="img" src="/a.png" alt="" />'))
        ->toBe('<img src="/a.png" alt="" />');
});

it('rejects tag names that are not tag names', function () {
    $this->render('<x-malevich::primitive as="div onclick=alert(1)" />');
})->throws(ViewException::class);

it('lets @attributes style a primitive from a component built on top of it', function () {
    expect($this->render('<x-ui-button variant="ghost" class="mt-2" disabled wire:click="go">Go</x-ui-button>'))
        ->toBe('<button class="btn btn-ghost mt-2" wire:click="go" type="button" data-disabled="" disabled="disabled"><span class="icon"></span> Go</button>');
});

it('turns the built component into a link when it gets an href', function () {
    expect($this->render('<x-ui-button href="/home">Home</x-ui-button>'))
        ->toBe('<a class="btn btn-primary" href="/home"><span class="icon"></span> Home</a>');
});

it('rewrites @attributes only inside component tags', function () {
    $compiled = ComponentTagCompiler::compile(<<<'BLADE'
        <x-foo a="b" @attributes('icon', merge: ['x' => 'y']) />
        <span @attributes></span>
        <x-bar title="mail@attributes.com" />
        @verbatim <x-baz @attributes /> @endverbatim
    BLADE);

    expect($compiled)
        ->toContain("<?php \$__malevichAttributes = \Malevich\Malevich::attributes(\$attributes ?? null, get_defined_vars(), 'icon', merge: ['x' => 'y']); ?><x-foo a=\"b\" :attributes=\"\$__malevichAttributes->toAttributeBag()\" />")
        ->toContain('<span @attributes></span>')
        ->toContain('title="mail@attributes.com"')
        ->toContain('<x-baz @attributes />');
});

it('lets the user override the tag of a component built on a primitive', function () {
    expect($this->render('<x-ui-button as="span">Hi</x-ui-button>'))
        ->toBe('<span class="btn btn-primary"><span class="icon"></span> Hi</span>');
});

it('refuses tags that are not UI elements', function (string $tag) {
    $this->render('<x-malevich::primitive :as="$tag">x</x-malevich::primitive>', ['tag' => $tag]);
})->with(['script', 'SCRIPT', 'iframe', 'style', 'object'])->throws(ViewException::class, 'refuses to render');
