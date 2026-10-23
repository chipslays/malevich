<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Malevich\Playground\Playground;

beforeEach(function () {
    Blade::anonymousComponentPath(__DIR__.'/../fixtures/playground', 'cat');
});

it('is off by default outside the local environment', function () {
    expect(Playground::enabled())->toBeFalse();

    config(['malevich.playground.enabled' => true]);
    expect(Playground::enabled())->toBeTrue();

    config(['malevich.playground.enabled' => 'false']);
    expect(Playground::enabled())->toBeFalse();

    config(['malevich.playground.enabled' => null]);
    app()->detectEnvironment(fn () => 'local');
    expect(Playground::enabled())->toBeTrue();
});

it('can be turned on or off from code, which wins over the config', function () {
    config(['malevich.playground.enabled' => false]);
    Malevich\Malevich::playground();
    expect(Playground::enabled())->toBeTrue();

    config(['malevich.playground.enabled' => true]);
    Malevich\Malevich::playground(false);
    expect(Playground::enabled())->toBeFalse();

    Malevich\Malevich::flush();
    expect(Playground::enabled())->toBeTrue();
});
it('lists the components with options, without the package ones', function () {
    $components = Playground::make()->components();

    expect($components)->toHaveKeys(['cat::chip', 'cat::broken', 'button'])
        ->not->toHaveKey('malevich::primitive')
        ->and($components['cat::chip']['options'])->toBe(['variant' => ['solid', 'outline'], 'size' => ['sm', 'md']]);
});

it('lists a component with empty class maps too, but not plain Blade components', function () {
    $playground = Playground::make();
    $components = $playground->components();

    expect($components)->toHaveKey('cat::fresh')
        ->not->toHaveKey('cat::plain')
        ->and($components['cat::fresh']['options'])->toBe([])
        ->and($playground->sheet('cat::fresh', $components['cat::fresh'])['default']->html)->toBe('<div class="fresh">Fresh</div>');

    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->get('/malevich?component=cat::fresh')->assertOk()->assertSee('No option values yet');
});

it('renders the default, every value of every option and a matrix of the first two', function () {
    $playground = Playground::make();
    $sheet = $playground->sheet('cat::chip', $playground->components()['cat::chip']);

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
    $playground = Playground::make();

    expect($playground->cell('toggle', ['checked' => 'true'], null)->tag)->toBe('<x-toggle checked />')
        ->and($playground->cell('toggle', ['checked' => 'false'], null)->tag)->toBe('<x-toggle :checked="false" />')
        ->and($playground->cell('toggle', ['checked' => 'false'], null)->html)->toBe('<span class="bg-gray-200"></span>')
        ->and($playground->cell('toggle', ['checked' => 'false'], null)->label())->toBe(':checked="false"');
});

it('shows why a component failed instead of breaking the page', function () {
    $cell = Playground::make()->cell('cat::broken', ['color' => 'blue'], 'Hi');

    expect($cell->html)->toBeNull()
        ->and($cell->error)->toContain('$missing');
});

it('takes the slot from the usage example in the leading comment', function () {
    $playground = Playground::make();

    expect($playground->example(__DIR__.'/../fixtures/playground/chip.blade.php'))->toBe('New')
        ->and($playground->example(__DIR__.'/../fixtures/playground/broken.blade.php'))->toBeNull()
        ->and($playground->sheet('cat::broken', $playground->components()['cat::broken'])['default']->tag)->toBe('<x-cat::broken>Broken</x-cat::broken>');
});

it('loads the project\'s own Vite entries, so its theme and JS apply', function () {
    $base = sys_get_temp_dir().'/malevich-vite-'.uniqid();
    File::makeDirectory("{$base}/resources/css", recursive: true);
    File::makeDirectory("{$base}/resources/js", recursive: true);
    File::put("{$base}/resources/css/site.css", '');
    File::put("{$base}/resources/js/app.js", '');

    expect(Playground::viteEntries($base))->toBe(['resources/js/app.js']);

    File::put("{$base}/vite.config.js", "laravel({\n    input: ['resources/css/site.css', 'resources/js/app.js', 'resources/js/missing.js'],\n    refresh: true,\n})");
    expect(Playground::viteEntries($base))->toBe(['resources/css/site.css', 'resources/js/app.js']);

    File::put("{$base}/vite.config.js", "laravel({ input: 'resources/css/site.css' })");
    expect(Playground::viteEntries($base))->toBe(['resources/css/site.css']);

    File::deleteDirectory($base);
});

it('serves the page when enabled', function () {
    config(['malevich.playground.enabled' => true, 'malevich.playground.head' => '<link rel="stylesheet" href="/app.css">']);
    Playground::routes();

    $this->get('/malevich?component=cat::chip')
        ->assertOk()
        ->assertSee('<link rel="stylesheet" href="/app.css">', false)
        ->assertSee('&lt;x-cat::chip&gt;', false)
        ->assertSee('<span class="chip-outline chip-sm">New</span>', false)
        ->assertSee('cat::broken');
});

it('warns when the app CSS is not loaded', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->get('/malevich')->assertOk()->assertSee('Your CSS isn\'t loaded', false);
});

it('never writes a component tag into its own views', function (string $view) {
    // Blade (and Livewire on top of it) compiles an opening x- tag anywhere in a view,
    // even inside a PHP string, so `"<x-{$name}>"` broke the page in some apps.
    expect(file_get_contents(__DIR__."/../../resources/views/{$view}.blade.php"))->not->toMatch('/<\s*x[-:]/');
})->with(['playground', 'playground-cell']);

it('renders the playground with the picked values and the usage example', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->postJson('/malevich/preview', ['component' => 'cat::chip', 'values' => ['variant' => 'outline', 'size' => 'bogus']])
        ->assertOk()
        ->assertExactJson([
            'tag' => '<x-cat::chip variant="outline">New</x-cat::chip>',
            'html' => '<span class="chip-outline chip-md">New</span>',
            'error' => null,
        ]);

    $this->postJson('/malevich/preview', ['component' => 'cat::chip', 'class' => 'mt-4'])
        ->assertJsonPath('html', '<span class="chip-solid chip-md mt-4">New</span>')
        ->assertJsonPath('tag', '<x-cat::chip class="mt-4">New</x-cat::chip>');

    $this->postJson('/malevich/preview', ['component' => 'nope'])->assertNotFound();
});

it('fills default and named slots from the slot fields, as plain text', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->postJson('/malevich/preview', [
        'component' => 'cat::card',
        'mode' => 'slots',
        'slots' => ['default' => '{{ 6 * 7 }} <b>', 'title' => 'Billing', 'bad name' => 'x'],
        'class' => 'w-64',
    ])
        ->assertJsonPath('html', '<div class="card w-64"><h3>Billing</h3>{{ 6 * 7 }} &lt;b&gt;</div>')
        ->assertJsonPath('tag', "<x-cat::card class=\"w-64\">\n    <x-slot:title>Billing</x-slot:title>\n    {{ 6 * 7 }} <b>\n</x-cat::card>");
});

it('passes attributes and slot classes from the fields, never as code', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->postJson('/malevich/preview', [
        'component' => 'cat::card',
        'mode' => 'slots',
        'slots' => ['default' => 'Body', 'title' => 'Billing'],
        'slotClasses' => ['title' => 'text-2xl'],
        'attributes' => [
            'class' => 'w-64',
            'id' => '{{ 6 * 7 }}"',
            'hidden' => 'true',
            ':title' => 'phpinfo()',
            '@click' => 'x',
        ],
    ])
        ->assertJsonPath('html', '<div class="card w-64" id="{{ 6 * 7 }}&quot;" hidden="hidden"><h3>Billing</h3>Body</div>')
        ->assertJsonPath('tag', "<x-cat::card class=\"w-64\" id=\"{{ 6 * 7 }}&quot;\" hidden>\n    <x-slot:title class=\"text-2xl\">Billing</x-slot:title>\n    Body\n</x-cat::card>");
});

it('offers the declared props as buttons in the playground', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    $this->get('/malevich?component=cat::card')
        ->assertOk()
        ->assertSee('data-mlv-prop="title"', false)
        ->assertDontSee('{{ $prop }}', false);
});

it('finds the props a component declares, without its options', function () {
    $playground = Playground::make();

    expect($playground->props($playground->components()['cat::card']))->toBe(['title' => ''])
        ->and($playground->props($playground->components()['cat::chip']))->toBe([])
        ->and($playground->slotClassesOf('<x-slot:title class="a b">T</x-slot:title><x-slot:footer>F</x-slot:footer>'))->toBe(['title' => 'a b']);
});

it('splits a usage example into slot fields', function () {
    expect(Playground::make()->slotsOf("<x-slot:title class=\"a\">Payment failed</x-slot:title>\nYour card\nwas declined.<x-slot name=\"footer\">Retry</x-slot>"))
        ->toBe(['default' => 'Your card was declined.', 'title' => 'Payment failed', 'footer' => 'Retry']);
});

it('runs Blade content only in the local environment', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();
    $blade = '<x-slot:title>{{ 6 * 7 }}</x-slot:title><x-cat::chip size="sm">Inner</x-cat::chip>';

    $this->postJson('/malevich/preview', ['component' => 'cat::card', 'mode' => 'blade', 'blade' => $blade])
        ->assertJsonPath('html', null)
        ->assertJsonPath('error', 'Blade content runs only in the local environment. Use the slot fields instead.');

    // Outside tests the web middleware checks the CSRF token, so the page sends it.
    app()->detectEnvironment(fn () => 'local');

    $this->withSession(['_token' => 'secret'])
        ->withHeader('X-CSRF-TOKEN', 'secret')
        ->postJson('/malevich/preview', ['component' => 'cat::card', 'mode' => 'blade', 'blade' => $blade])
        ->assertJsonPath('html', '<div class="card"><h3>42</h3><span class="chip-solid chip-sm">Inner</span></div>');
});

it('protects the playground with a POST and a CSRF token', function () {
    config(['malevich.playground.enabled' => true]);
    Playground::routes();

    // Another site could make the browser send a GET or a POST - but never with the token.
    $this->get('/malevich/preview?component=cat::chip&mode=blade&blade=x')->assertStatus(405);

    app()->detectEnvironment(fn () => 'local');
    $this->withSession(['_token' => 'secret'])
        ->postJson('/malevich/preview', ['component' => 'cat::chip', 'mode' => 'blade', 'blade' => 'x'])
        ->assertStatus(419);
});

it('is a 404 when disabled', function () {
    Playground::routes();

    $this->get('/malevich')->assertNotFound();
});
