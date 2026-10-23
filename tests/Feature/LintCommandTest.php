<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->dir = sys_get_temp_dir().'/malevich-lint-'.uniqid();

    $components = [
        'button' => <<<'BLADE'
            @variant(['primary' => 'a', 'outline' => 'b'])
            @size(['sm' => 'c', 'md' => 'd'])
            @size('icon', ['lg' => 'e'])
            @compound(['variant' => 'ghost', 'loading' => true], $dynamic)
            {{-- @variant(['commented' => 'x']) --}}
            <button @attributes>{{ $slot }}</button>
            BLADE,
        'dynamic' => "@variant(\$variants)\n@size(['sm' => 'x'])\n<div @attributes></div>",
        'named' => "@option(\$name, ['a' => 'b'])\n<div @attributes></div>",
        'shared' => "@include('lint-partials.sizes', ['x' => \$y])\n<div @attributes></div>",
        'conditional' => "@includeIf('lint-partials.sizes')\n@variant(['a' => 'b'])\n<div @attributes></div>",
        'free' => "@color('text-red-500')\n<div @attributes></div>",
        'cards/index' => "@variant(['flat' => 'a'])\n<div @attributes></div>",
        'targeted' => "@variant(['flat' => 'a'])\n@compound(['variant' => 'raised'], targets: ['icon' => \$classes])\n<div @attributes></div>",
        'scoped' => "@props(['iconStyle' => 'solid'])\n@option('iconStyle', ['solid' => 'a', 'outline' => 'b'])\n<div @attributes></div>",
        'cased' => <<<'BLADE'
            @cases('size', [
                'sm' => ['default' => ['px-3' => ! $iconOnly], 'icon' => $iconClasses],
                'lg' => ['default' => $classes, 'icon' => 'large-icon'],
            ])
            <div @attributes></div>
            BLADE,
        'when-cased' => <<<'BLADE'
            @cases('size', ['sm' => ['-mx-2' => ! $iconOnly]], when: [
                'inset' => true,
                'variant' => ['ghost', 'link'],
            ])
            <div @attributes></div>
            BLADE,
    ];

    foreach ($components as $name => $source) {
        $this->files->ensureDirectoryExists(dirname("{$this->dir}/components/{$name}.blade.php"));
        $this->files->put("{$this->dir}/components/{$name}.blade.php", $source);
    }

    $this->files->ensureDirectoryExists("{$this->dir}/lint-partials");
    $this->files->put("{$this->dir}/lint-partials/sizes.blade.php", "@size(['xl' => 'h-12'])");

    $this->files->ensureDirectoryExists("{$this->dir}/views");

    Blade::anonymousComponentPath("{$this->dir}/components", 'lint');
    $this->app['view']->addLocation($this->dir);
});

afterEach(fn () => $this->files->deleteDirectory($this->dir));

function page(string $dir, string $source): string
{
    file_put_contents("{$dir}/views/page.blade.php", $source);

    return "{$dir}/views";
}

it('reports values a component does not define, with the line and a suggestion', function () {
    $views = page($this->dir, <<<'BLADE'
        <x-lint::button variant="primary" size="sm">ok</x-lint::button>
        <x-lint::button variant="primay">typo</x-lint::button>
        <x-lint::button
            size="xl" />
        BLADE);

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('page.blade.php:2')
        ->expectsOutputToContain('variant [primay] is not defined; expected one of: primary, outline, ghost. Did you mean [primary]?')
        ->expectsOutputToContain('page.blade.php:3')
        ->expectsOutputToContain('size [xl] is not defined; expected one of: sm, md, lg.')
        ->expectsOutputToContain('2 problems in 3 tags of')
        ->assertFailed();
});

it('never reports what it cannot know', function () {
    $views = page($this->dir, <<<'BLADE'
        <x-lint::button :variant="$v" variant2="x" loading="false" />
        <x-lint::button variant="{{ $v }}" class="anything" />
        <x-lint::button variant="ghost" loading="true" />
        {{-- <x-lint::button variant="nope" /> --}}
        <x-lint::dynamic variant="anything" />
        <x-lint::named variant="anything" />
        <x-lint::conditional variant="anything" />
        <x-lint::free color="anything" />
        <x-lint::targeted variant="raised" />
        <x-lint::shared size="xl" />
        <x-other variant="anything" />
        BLADE);

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('No problems')
        ->assertSuccessful();
});

it('checks target option values including modifier syntax', function () {
    $views = page($this->dir, '<x-lint::button size="sm" icon:size="lg" /><x-lint::button icon:size.rounded.scale[2]="typo" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('icon:size.rounded.scale[2] [typo] is not defined')
        ->expectsOutputToContain('1 problem')
        ->assertFailed();
});

it('does not validate external namespaces as component options', function () {
    $views = page($this->dir, '<x-lint::button wire:loading.attr="disabled" x:variant="external" x-bind:size="computed" analytics:size.once.limit[24]="external" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('No problems')
        ->assertSuccessful();
});

it('checks cases with literal option values and dynamic classes', function () {
    $views = page($this->dir, '<x-lint::cased size="sm" icon:size="lg" /><x-lint::cased icon:size="typo" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('icon:size [typo] is not defined; expected one of: sm, lg.')
        ->assertFailed();
});

it('checks shared cases conditions without mistaking root class keys for targets', function () {
    $views = page($this->dir, '<x-lint::when-cased size="sm" variant="ghost" -mx-2:size="external" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('No problems')->assertSuccessful();

    $views = page($this->dir, '<x-lint::when-cased size="sm" variant="typo" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('variant [typo] is not defined; expected one of: ghost, link.')
        ->assertFailed();
});

it('checks a scoped prop alias including its decorated form', function () {
    $views = page($this->dir, '<x-lint::scoped icon:style="solid" /><x-lint::scoped icon:style.filled[false]="typo" />');

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('icon:style.filled[false] [typo] is not defined')
        ->expectsOutputToContain('1 problem')
        ->assertFailed();
});

it('checks literal maps next to unknown ones and maps from partials', function () {
    $views = page($this->dir, "<x-lint::dynamic size=\"xs\" />\n<x-lint::shared size=\"xxl\" />\n<x-lint::cards variant=\"round\" />\n<x-lint::cards.index variant=\"round\" />");

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('size [xs] is not defined; expected one of: sm.')
        ->expectsOutputToContain('size [xxl] is not defined; expected one of: xl.')
        ->expectsOutputToContain('4 problems')
        ->assertFailed();
});

it('writes GitHub Actions annotations', function () {
    $views = page($this->dir, "\n<x-lint::button variant=\"primay\" />");

    $this->artisan('malevich:lint', ['paths' => [$views], '--format' => 'github'])
        ->expectsOutputToContain(',line=2::<x-lint::button>: variant [primay] is not defined')
        ->assertFailed();
});
