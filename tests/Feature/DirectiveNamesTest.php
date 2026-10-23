<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Malevich\MalevichServiceProvider;

function reboot(array $config = []): void
{
    config($config);
    (new MalevichServiceProvider(app()))->boot();
}

it('boots again without mistaking its own directives for a clash', function () {
    reboot();
    reboot();

    expect(Blade::compileString("@cases('variant', [])"))->toContain('->cases(');
});

it('refuses a directive name that is a built-in Blade directive', function (string $role) {
    reboot(["malevich.directives.{$role}" => 'if']);
})->with(['attributes', 'base', 'option', 'compound', 'cases', 'styled'])->throws(InvalidArgumentException::class, 'is a built-in Blade directive');

it('refuses a name that another package already registered', function (string $role) {
    Blade::directive('taken', fn () => '');

    reboot(["malevich.directives.{$role}" => 'taken']);
})->with(['attributes', 'base', 'option', 'compound', 'cases', 'styled'])->throws(InvalidArgumentException::class, 'by your application or another package');

it('refuses an option that another package already registered', function () {
    Blade::directive('radius', fn () => '');

    reboot(['malevich.options' => ['radius']]);
})->throws(InvalidArgumentException::class, 'by your application or another package');

it('refuses invalid or doubled names', function (array $config, string $message) {
    expect(fn () => reboot($config))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'invalid' => [['malevich.directives.cases' => 'my-theme'], 'is not a valid directive name'],
    'cases = attributes' => [['malevich.directives.cases' => 'attributes'], 'used both as directives.attributes and directives.cases'],
    'styled = base' => [['malevich.directives.styled' => 'base'], 'used both as directives.base and directives.styled'],
    'option as an option' => [['malevich.options' => ['variant', 'option']], 'used both as directives.option and in options'],
    'styled as an option' => [['malevich.options' => ['styled']], 'used both as directives.styled and in options'],
]);

it('refuses to boot when a directive name is taken by another package', function () {
    // A fresh compiler that already has someone else's @base, as if their
    // provider had booted before ours.
    Blade::swap(new BladeCompiler(app('files'), sys_get_temp_dir()));
    Blade::directive('base', fn () => '');

    reboot();
})->throws(InvalidArgumentException::class, '[@base] is already registered as a Blade directive by your application or another package. Pick another name');

it('renames every directive', function () {
    reboot(['malevich.directives' => [
        'attributes' => 'paint',
        'base' => 'always',
        'option' => 'axis',
        'compound' => 'when',
        'cases' => 'palette',
        'styled' => 'present',
    ]]);

    expect(Blade::compileString('<b @paint>'))->toContain('Malevich::attributes($attributes ?? null, get_defined_vars())')
        ->and(Blade::compileString("@always('x')"))->toContain("->base('x')")
        ->and(Blade::compileString("@axis('radius', [])"))->toContain("->option('radius', [])")
        ->and(Blade::compileString("@when(['size' => 'sm'], 'x')"))->toContain("->compound(['size' => 'sm'], 'x')")
        ->and(Blade::compileString("@palette('variant', [])"))->toContain('->cases(')
        ->and(Blade::compileString("@present('glow')"))->toContain("Malevich::styled(\$attributes ?? null, get_defined_vars(), 'glow')");
});

it('renders a component written with renamed directives', function () {
    reboot(['malevich.directives.attributes' => 'paint', 'malevich.directives.base' => 'always']);

    expect(trim(Blade::render("@php(\$attributes = new Illuminate\\View\\ComponentAttributeBag(['class' => 'x']))\n@always('a')\n<b @paint></b>", deleteCachedView: true)))
        ->toBe('<b class="a x"></b>');
});

it('closes the styled directive with @end plus its name', function () {
    expect(Blade::compileString("@styled('glow')x @endstyled"))->toEndWith('x <?php endif; ?>');

    reboot(['malevich.directives.styled' => 'present']);

    expect(Blade::compileString("@present('glow')x @endpresent"))->toEndWith('x <?php endif; ?>');
});

it('refuses a styled directive whose closing tag is taken by another package', function () {
    Blade::swap(new BladeCompiler(app('files'), sys_get_temp_dir()));
    Blade::directive('endstyled', fn () => '');

    reboot();
})->throws(InvalidArgumentException::class, 'by your application or another package');

it('leaves CSS alone when a directive has no expression', function () {
    $css = "<style type=\"text/tailwindcss\">\n@theme {\n  --color-brand: red;\n}\n@variant dark (&:where(.dark, .dark *));\n@base\n</style>";

    expect(Blade::compileString($css))->toBe($css);
});

it('still compiles directives that have an expression', function () {
    expect(Blade::compileString("@variant(['a' => 'b'])"))->toContain("->option('variant', ['a' => 'b'])")
        ->and(Blade::compileString("@cases('variant', [])"))->toContain("->cases('variant', [])");
});
