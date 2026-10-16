<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Malevich\Catalog\Catalog;

beforeEach(function () {
    Blade::anonymousComponentPath(__DIR__.'/../fixtures/catalog', 'cat');
});

it('is off by default outside the local environment', function () {
    expect(Catalog::enabled())->toBeFalse();

    config(['malevich.catalog.enabled' => true]);
    expect(Catalog::enabled())->toBeTrue();

    config(['malevich.catalog.enabled' => 'false']);
    expect(Catalog::enabled())->toBeFalse();

    config(['malevich.catalog.enabled' => null]);
    app()->detectEnvironment(fn () => 'local');
    expect(Catalog::enabled())->toBeTrue();
});

it('lists the components with options, without the package ones', function () {
    $components = Catalog::make()->components();

    expect($components)->toHaveKeys(['cat::chip', 'cat::broken', 'button'])
        ->not->toHaveKey('malevich::primitive')
        ->and($components['cat::chip']['options'])->toBe(['variant' => ['solid', 'outline'], 'size' => ['sm', 'md']]);
});

it('renders the default, every value of every option and a matrix of the first two', function () {
    $catalog = Catalog::make();
    $sheet = $catalog->sheet('cat::chip', $catalog->components()['cat::chip']);

    expect($sheet['default']->tag)->toBe('<x-cat::chip>New</x-cat::chip>')
        ->and($sheet['default']->html)->toBe('<span class="chip-solid chip-md">New</span>')
        ->and(array_map(fn ($cell) => $cell->label(), $sheet['options']['variant']))->toBe(['variant="solid"', 'variant="outline"'])
        ->and($sheet['options']['size'][0]->html)->toBe('<span class="chip-solid chip-sm">New</span>')
        ->and($sheet['matrix']['rows'])->toBe('variant')
        ->and($sheet['matrix']['columns'])->toBe('size')
        ->and($sheet['matrix']['cells']['outline']['sm']->tag)->toBe('<x-cat::chip variant="outline" size="sm">New</x-cat::chip>')
        ->and($sheet['matrix']['cells']['outline']['sm']->html)->toBe('<span class="chip-outline chip-sm">New</span>');
});

it('writes boolean values as a flag or a bound false', function () {
    $catalog = Catalog::make();

    expect($catalog->cell('toggle', ['checked' => 'true'], null)->tag)->toBe('<x-toggle checked />')
        ->and($catalog->cell('toggle', ['checked' => 'false'], null)->tag)->toBe('<x-toggle :checked="false" />')
        ->and($catalog->cell('toggle', ['checked' => 'false'], null)->html)->toBe('<span class="bg-gray-200"></span>')
        ->and($catalog->cell('toggle', ['checked' => 'false'], null)->label())->toBe(':checked="false"');
});

it('shows why a component failed instead of breaking the page', function () {
    $cell = Catalog::make()->cell('cat::broken', ['color' => 'blue'], 'Hi');

    expect($cell->html)->toBeNull()
        ->and($cell->error)->toContain('$missing');
});

it('takes the slot from the usage example in the leading comment', function () {
    $catalog = Catalog::make();

    expect($catalog->example(__DIR__.'/../fixtures/catalog/chip.blade.php'))->toBe('New')
        ->and($catalog->example(__DIR__.'/../fixtures/catalog/broken.blade.php'))->toBeNull()
        ->and($catalog->sheet('cat::broken', $catalog->components()['cat::broken'])['default']->tag)->toBe('<x-cat::broken>Broken</x-cat::broken>');
});

it('loads the project\'s own Vite entries, so its theme and JS apply', function () {
    $base = sys_get_temp_dir().'/malevich-vite-'.uniqid();
    File::makeDirectory("{$base}/resources/css", recursive: true);
    File::makeDirectory("{$base}/resources/js", recursive: true);
    File::put("{$base}/resources/css/site.css", '');
    File::put("{$base}/resources/js/app.js", '');

    expect(Catalog::viteEntries($base))->toBe(['resources/js/app.js']);

    File::put("{$base}/vite.config.js", "laravel({\n    input: ['resources/css/site.css', 'resources/js/app.js', 'resources/js/missing.js'],\n    refresh: true,\n})");
    expect(Catalog::viteEntries($base))->toBe(['resources/css/site.css', 'resources/js/app.js']);

    File::put("{$base}/vite.config.js", "laravel({ input: 'resources/css/site.css' })");
    expect(Catalog::viteEntries($base))->toBe(['resources/css/site.css']);

    File::deleteDirectory($base);
});

it('serves the page when enabled', function () {
    config(['malevich.catalog.enabled' => true, 'malevich.catalog.head' => '<link rel="stylesheet" href="/app.css">']);
    Catalog::routes();

    $this->get('/malevich?component=cat::chip')
        ->assertOk()
        ->assertSee('<link rel="stylesheet" href="/app.css">', false)
        ->assertSee('&lt;x-cat::chip&gt;', false)
        ->assertSee('<span class="chip-outline chip-sm">New</span>', false)
        ->assertSee('cat::broken');
});

it('warns when the app CSS is not loaded', function () {
    config(['malevich.catalog.enabled' => true]);
    Catalog::routes();

    $this->get('/malevich')->assertOk()->assertSee('Your CSS isn\'t loaded', false);
});

it('is a 404 when disabled', function () {
    Catalog::routes();

    $this->get('/malevich')->assertNotFound();
});
