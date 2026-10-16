<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Malevich\Malevich;

function renderRaw(string $template): string
{
    return Blade::render($template, deleteCachedView: true);
}

it('prints nothing by default', function () {
    expect(Malevich::isDebugging())->toBeFalse()
        ->and(renderRaw('<x-button variant="ghost">Hi</x-button>'))->not->toContain('<!--');
});

it('explains every element in a comment in front of it', function () {
    Malevich::debug();

    $html = renderRaw('<x-button variant="ghost" :size="[\'icon\' => \'lg\']" icon class="mt-2">Hi</x-button>');

    expect($html)->toContain(<<<'HTML'
        <!--
             ┌─ ⬛ <x-button variant="ghost">
             │
             ├─ 🔹 variant = ghost ← from tag
             │
             ├─ 🔸 variant = * → btn
             ├─ 🔸 variant = ghost → btn-ghost
             ├─ 🔸 class from the tag → mt-2
             │
             └─ ✅ btn btn-ghost mt-2
        -->
        <button class="btn btn-ghost mt-2"
        HTML)->toContain(<<<'HTML'
        <!--
             ┌─ ⬛ <x-button variant="ghost" size="lg"> › icon
             │
             ├─ 🔹 size = lg ← from tag
             │
             ├─ 🔸 size = lg → w-6
             │
             └─ ✅ w-6
        -->
        <svg class="w-6">
        HTML);
});

it('tells a value from the tag apart from a @props default', function () {
    Malevich::debug();

    expect(renderRaw('<x-strict-button size="sm">Go</x-strict-button>'))
        ->toContain('├─ 🔹 variant = primary ← from props')
        ->toContain('├─ 🔹 size = sm ← from tag');
});

it('lists the matching @compound and values without classes', function () {
    Malevich::debug();

    expect(renderRaw('<x-strict-button size="xl">Go</x-strict-button>'))->toContain('├─ ⚠️ size = xl → no classes for this value')
        ->and(renderRaw('<x-strict-button>Go</x-strict-button>'))->toContain('├─ 🔸 @compound(variant=primary, size=md) → shadow');
});

it('shows the class written inside the component', function () {
    Malevich::debug();

    expect(renderRaw('<x-inline-base />'))->toContain("├─ 🔸 class=\"\" → px-2 rounded\n     ├─ 🔸 size = md → px-4\n     ├─ 🔸 merge: → m-1");
});

it('shows what class merging removed', function () {
    Malevich::debug();
    Malevich::mergeClassesUsing(fn (string $classes) => str_replace('btn-ghost', '', $classes));

    expect(renderRaw('<x-button variant="ghost">Hi</x-button>'))->toContain('├─ 🧹 tailwind-merge removed → btn-ghost');
});

it('explains elements resolved for component tags', function () {
    Malevich::debug();

    expect(renderRaw('<x-ui-button>Go</x-ui-button>'))->toContain("┌─ ⬛ <x-ui-button variant=\"primary\">\n     │\n     ├─ 🔹 variant = primary ← from props");
});

it('never breaks out of the comment', function () {
    Malevich::debug();

    $comment = str(renderRaw('<x-unsafe-classes classes="bg-(--brand) -->" />'))->between('<!--', '-->');

    expect((string) $comment)->toContain('bg-(- -brand) - ->')->toContain('└─ ✅');
});

it('is switched on and off in config, and a call in code wins', function () {
    config(['malevich.debug' => true]);
    Malevich::flushConfig();

    expect(Malevich::isDebugging())->toBeTrue()
        ->and(renderRaw('<x-toggle />'))->toContain('┌─ ⬛ <x-toggle>');

    Malevich::debug(false);

    expect(Malevich::isDebugging())->toBeFalse()
        ->and(renderRaw('<x-toggle />'))->not->toContain('<!--');

    config(['malevich.debug' => false]);
    Malevich::flushConfig();
    Malevich::debug();

    expect(Malevich::isDebugging())->toBeTrue();
});

it('switches strict mode in config too, and a call in code wins', function () {
    config(['malevich.strict' => true]);
    Malevich::flushConfig();

    expect(fn () => renderRaw('<x-strict-button size="xl">Go</x-strict-button>'))->toThrow(ViewException::class, 'size [xl] is not defined');

    Malevich::strict(false);

    expect(renderRaw('<x-strict-button size="xl">Go</x-strict-button>'))->toContain('<button');
});
