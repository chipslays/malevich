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
            @preset('danger', ['variant' => $variant])
            {{-- @variant(['commented' => 'x']) --}}
            <button @ui>{{ $slot }}</button>
            BLADE,
        'dynamic' => "@variant(\$variants)\n@size(['sm' => 'x'])\n<div @ui></div>",
        'named' => "@directive(\$name, ['a' => 'b'])\n<div @ui></div>",
        'shared' => "@include('lint-partials.sizes', ['x' => \$y])\n<div @ui></div>",
        'conditional' => "@includeIf('lint-partials.sizes')\n@variant(['a' => 'b'])\n<div @ui></div>",
        'free' => "@color('text-red-500')\n<div @ui></div>",
        'cards/index' => "@variant(['flat' => 'a'])\n<div @ui></div>",
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
            size="xl" preset="dangr" />
        BLADE);

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('page.blade.php:2')
        ->expectsOutputToContain('variant [primay] is not defined; expected one of: primary, outline, ghost. Did you mean [primary]?')
        ->expectsOutputToContain('page.blade.php:3')
        ->expectsOutputToContain('size [xl] is not defined; expected one of: sm, md, lg.')
        ->expectsOutputToContain('preset [dangr] is not defined; expected one of: danger. Did you mean [danger]?')
        ->expectsOutputToContain('3 problems in 3 tags of')
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
        <x-lint::shared size="xl" />
        <x-other variant="anything" />
        BLADE);

    $this->artisan('malevich:lint', ['paths' => [$views]])
        ->expectsOutputToContain('No problems')
        ->assertSuccessful();
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
