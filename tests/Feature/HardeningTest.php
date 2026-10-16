<?php

use Illuminate\Support\Facades\File;
use Illuminate\View\ComponentAttributeBag;
use Malevich\Element;
use Malevich\Malevich;
use Malevich\MalevichServiceProvider;
use Malevich\Support\ClassList;
use Malevich\Support\ComponentTagCompiler;
use Malevich\Support\HtmlTagCompiler;

function bootWith(array $config): void
{
    config($config);
    (new MalevichServiceProvider(app()))->boot();
}

it('refuses option names that would replace built-in Blade directives', function (string $name) {
    bootWith(['malevich.options' => [$name]]);
})->with(['if', 'class', 'foreach', 'props'])->throws(InvalidArgumentException::class, 'is a built-in Blade directive');

it('refuses option names that clash with Malevich directives', function () {
    bootWith(['malevich.options' => ['preset']]);
})->throws(InvalidArgumentException::class, 'is already a Malevich directive');

it('refuses option names that are methods of $attributes', function () {
    bootWith(['malevich.options' => ['merge']]);
})->throws(InvalidArgumentException::class, 'is already a method of $attributes');

it('refuses invalid option names', function () {
    bootWith(['malevich.options' => ['my-size']]);
})->throws(InvalidArgumentException::class, 'is not a valid directive name');

it('refuses a render directive that is also an option', function () {
    bootWith(['malevich.options' => ['variant', 'attributes']]);
})->throws(InvalidArgumentException::class, 'used both as render_directive and in options');

it('accepts the default and custom names', function () {
    bootWith(['malevich.options' => ['variant', 'size', 'color', 'radius', 'tone'], 'malevich.render_directive' => 'styles']);

    expect(true)->toBeTrue();
});

it('does not rewrite @attributes inside quoted attribute values or echoes', function () {
    $compiled = ComponentTagCompiler::compile(<<<'BLADE'
        <x-foo title="press @attributes now" :label="'@attributes'" data-x='a @attributes b' @attributes />
    BLADE);

    expect($compiled)
        ->toContain('title="press @attributes now"')
        ->toContain(":label=\"'@attributes'\"")
        ->toContain("data-x='a @attributes b'")
        ->toContain('<?php $__malevichAttributes = \Malevich\Malevich::attributes($attributes ?? null, get_defined_vars()); ?><x-foo')
        ->toContain(':attributes="$__malevichAttributes->toAttributeBag()"');
});

it('splits multi-line class strings on any whitespace', function () {
    $classes = (new ClassList)->add("inline-flex\n    items-center\tgap-2")->add('items-center');

    expect((string) $classes)->toBe('inline-flex items-center gap-2');
});

it('rejects component names that could leave the components folder', function (string $name) {
    config(['malevich.components.path' => sys_get_temp_dir().'/malevich-'.uniqid()]);

    $this->artisan('make:malevich', ['name' => $name])->assertFailed();
})->with(['C:/Windows/evil', 'foo bar', 'a$b', '"']);

it('neutralizes dots in component names', function () {
    $path = sys_get_temp_dir().'/malevich-'.uniqid();
    config(['malevich.components.path' => $path]);

    $this->artisan('make:malevich', ['name' => '../../escape'])->assertSuccessful();

    expect($path.'/escape.blade.php')->toBeFile();

    File::deleteDirectory($path);
});

it('finds @attributes on a component tag with ">" inside a quoted attribute', function () {
    expect($this->render('<x-alpine />'))->toBe('<span x-show="count > 0" class="v-a"><i class="ic" title="a)b"></i></span>');
});

it('keeps double quotes and parentheses in @attributes arguments on component tags', function () {
    expect(ComponentTagCompiler::compile('<x-foo @attributes("icon", merge: [\'title\' => "a)b"]) />'))
        ->toBe('<?php $__malevichAttributes = \Malevich\Malevich::attributes($attributes ?? null, get_defined_vars(), "icon", merge: [\'title\' => "a)b"]); ?><x-foo :attributes="$__malevichAttributes->toAttributeBag()" />');
});

it('leaves the template untouched when the regex gives up', function () {
    $jit = ini_get('pcre.jit');
    $limit = ini_get('pcre.backtrack_limit');
    ini_set('pcre.jit', '0');
    ini_set('pcre.backtrack_limit', '1');

    try {
        expect(ComponentTagCompiler::compile('<x-foo a="b" @attributes />'))->toBe('<x-foo a="b" @attributes />')
            ->and(HtmlTagCompiler::compile('<b a="b" class="c" @attributes>'))->toBe('<b a="b" class="c" @attributes>');
    } finally {
        ini_set('pcre.jit', (string) $jit);
        ini_set('pcre.backtrack_limit', (string) $limit);
    }
});

it('explains that @attributes needs a component', function () {
    $this->render('<p @attributes></p>');
})->throws(Exception::class, 'only work inside a Blade component');

it('does not let a quote in a class map break out of the attribute', function () {
    expect($this->render('<x-unsafe-classes :classes="$classes" />', ['classes' => '"><script>alert(1)</script>']))
        ->toBe('<div class="&quot;><script>alert(1)</script>"></div>');
});

it('does not escape the classes a second time', function () {
    expect($this->render('<x-unsafe-classes classes="a" class="{{ \'b&c\' }}" />'))->toBe('<div class="a b&amp;c"></div>');
});

it('keeps memoized results per element', function () {
    $bag = new ComponentAttributeBag;
    Malevich::recipe($bag)->option('size', ['sm' => 's', 'lg' => 'l']);

    $small = (new Element($bag))->size('sm');

    expect($small->toClasses())->toBe('s')
        ->and($small->size('lg')->toClasses())->toBe('l')
        ->and($small->toClasses())->toBe('s')
        ->and((string) $small->merge(['id' => 'x']))->toBe('class="s" id="x"');
});
