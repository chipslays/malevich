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

it('refuses a cases or has directive that is a built-in Blade directive', function (string $option) {
    reboot(['malevich.'.$option => 'if']);
})->with(['cases_directive', 'has_directive'])->throws(InvalidArgumentException::class, 'is a built-in Blade directive');

it('refuses a name that another package already registered', function (string $option) {
    Blade::directive('taken', fn () => '');

    reboot(['malevich.'.$option => 'taken']);
})->with(['cases_directive', 'has_directive', 'render_directive'])->throws(InvalidArgumentException::class, 'by your application or another package');

it('refuses an axis that another package already registered', function () {
    Blade::directive('radius', fn () => '');

    reboot(['malevich.directives' => ['radius']]);
})->throws(InvalidArgumentException::class, 'by your application or another package');

it('refuses invalid or doubled names', function (array $config, string $message) {
    reboot($config);
})->with([
    'cases invalid' => [['malevich.cases_directive' => 'my-theme'], 'is not a valid directive name'],
    'cases = render' => [['malevich.cases_directive' => 'ui'], 'used both as render_directive and cases_directive'],
    'has = cases' => [['malevich.has_directive' => 'cases'], 'used both as cases_directive and has_directive'],
    'cases as axis' => [['malevich.directives' => ['variant', 'cases']], 'used both as cases_directive and in directives'],
    'has as axis' => [['malevich.directives' => ['has']], 'used both as has_directive and in directives'],
])->throws(InvalidArgumentException::class);

it('refuses a cases directive that is a fixed Malevich directive', function () {
    reboot(['malevich.cases_directive' => 'base']);
})->throws(InvalidArgumentException::class, 'is already a Malevich directive');

it('refuses to boot when a fixed name is taken by another package', function () {
    // A fresh compiler that already has someone else's @base, as if their
    // provider had booted before ours.
    Blade::swap(new BladeCompiler(app('files'), sys_get_temp_dir()));
    Blade::directive('base', fn () => '');

    reboot();
})->throws(InvalidArgumentException::class, 'cannot be changed');

it('renames the cases and has directives', function () {
    reboot(['malevich.cases_directive' => 'palette', 'malevich.has_directive' => 'present']);

    expect(Blade::compileString("@palette('variant', [])"))->toContain('->cases(')
        ->and(Blade::compileString("@present('glow')"))->toContain("Malevich::has(\$attributes, get_defined_vars(), 'glow')");
});

it('closes the has directive with @end plus its name', function () {
    expect(Blade::compileString("@has('glow')x @endhas"))->toEndWith('x <?php endif; ?>');

    reboot(['malevich.has_directive' => 'present']);

    expect(Blade::compileString("@present('glow')x @endpresent"))->toEndWith('x <?php endif; ?>');
});

it('refuses a has directive whose closing tag is taken by another package', function () {
    Blade::swap(new BladeCompiler(app('files'), sys_get_temp_dir()));
    Blade::directive('endhas', fn () => '');

    reboot();
})->throws(InvalidArgumentException::class, 'by your application or another package');

it('leaves CSS alone when a directive has no expression', function () {
    $css = "<style type=\"text/tailwindcss\">\n@theme {\n  --color-brand: red;\n}\n@variant dark (&:where(.dark, .dark *));\n@base\n</style>";

    expect(Blade::compileString($css))->toBe($css);
});

it('still compiles directives that have an expression', function () {
    expect(Blade::compileString("@variant(['a' => 'b'])"))->toContain("->axis('variant', ['a' => 'b'])")
        ->and(Blade::compileString("@cases('variant', [])"))->toContain("->cases('variant', [])");
});
