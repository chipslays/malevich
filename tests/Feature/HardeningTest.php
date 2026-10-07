<?php

use Illuminate\Support\Facades\File;
use Malevich\MalevichServiceProvider;
use Malevich\Support\ClassList;
use Malevich\Support\ComponentTagCompiler;

function bootWith(array $config): void
{
    config($config);
    (new MalevichServiceProvider(app()))->boot();
}

it('refuses directive names that would replace built-in Blade directives', function (string $name) {
    bootWith(['malevich.directives' => [$name]]);
})->with(['if', 'class', 'foreach', 'props'])->throws(InvalidArgumentException::class, 'is a built-in Blade directive');

it('refuses directive names that clash with Malevich directives', function () {
    bootWith(['malevich.directives' => ['preset']]);
})->throws(InvalidArgumentException::class, 'is already a Malevich directive');

it('refuses directive names that are methods of $attributes', function () {
    bootWith(['malevich.directives' => ['merge']]);
})->throws(InvalidArgumentException::class, 'is already a method of $attributes');

it('refuses invalid directive names', function () {
    bootWith(['malevich.directives' => ['my-size']]);
})->throws(InvalidArgumentException::class, 'is not a valid directive name');

it('refuses a render directive that is also an axis', function () {
    bootWith(['malevich.directives' => ['variant', 'ui']]);
})->throws(InvalidArgumentException::class, 'used both as render_directive and in directives');

it('accepts the default and custom names', function () {
    bootWith(['malevich.directives' => ['variant', 'size', 'color', 'radius', 'tone'], 'malevich.render_directive' => 'styles']);

    expect(true)->toBeTrue();
});

it('does not rewrite @ui inside quoted attribute values or echoes', function () {
    $compiled = ComponentTagCompiler::compile(<<<'BLADE'
        <x-foo title="press @ui now" :label="'@ui'" data-x='a @ui b' @ui />
    BLADE);

    expect($compiled)
        ->toContain('title="press @ui now"')
        ->toContain(":label=\"'@ui'\"")
        ->toContain("data-x='a @ui b'")
        ->toContain(':attributes="\Malevich\Malevich::ui($attributes, get_defined_vars())->toAttributeBag()"');
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
