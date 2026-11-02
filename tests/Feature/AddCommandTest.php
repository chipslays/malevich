<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->kitDestination = sys_get_temp_dir().'/malevich-add-'.uniqid();
    config(['malevich.components.path' => $this->kitDestination]);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->kitDestination);
});

it('copies a kit component into the configured directory without altering its source', function () {
    $this->artisan('malevich:add', ['name' => 'button'])
        ->expectsOutputToContain('Kit component added.')
        ->expectsOutputToContain('<x-button>')
        ->assertSuccessful();

    expect(file_get_contents($this->kitDestination.'/button/index.blade.php'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/kit/button/index.blade.php'));

    Blade::anonymousComponentPath($this->kitDestination, 'added');

    expect($this->render('<x-added::button>Save</x-added::button>'))
        ->toContain('<button', 'tone-brand', 'rounded-brand', 'Save');
});

it('accepts a Blade filename and reports the configured component prefix', function () {
    config(['malevich.components.prefix' => 'ui']);

    $this->artisan('malevich:add', ['name' => 'button.blade.php'])
        ->expectsOutputToContain('<x-ui::button>')->assertSuccessful();

    expect($this->kitDestination.'/button/index.blade.php')->toBeFile();
});

it('preserves existing components unless force is explicit', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->kitDestination.'/button');
    $files->put($this->kitDestination.'/button/index.blade.php', 'custom button');

    $this->artisan('malevich:add', ['name' => 'button'])
        ->expectsOutputToContain('--force')->assertFailed();

    expect(file_get_contents($this->kitDestination.'/button/index.blade.php'))->toBe('custom button');

    $this->artisan('malevich:add', ['name' => 'button', '--force' => true])
        ->expectsOutputToContain('Kit component overwritten.')->assertSuccessful();

    expect(file_get_contents($this->kitDestination.'/button/index.blade.php'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/kit/button/index.blade.php'));
});

it('lists available components for an unknown kit name without creating files', function () {
    $this->artisan('malevich:add', ['name' => 'missing'])
        ->expectsOutputToContain('does not exist')
        ->expectsOutputToContain('Available: avatar, badge, button')->assertFailed();

    expect(is_dir($this->kitDestination))->toBeFalse();
});

it('rejects traversal and absolute paths', function (string $name) {
    $this->artisan('malevich:add', ['name' => $name])
        ->expectsOutputToContain('Invalid component name.')->assertFailed();

    expect(is_dir($this->kitDestination))->toBeFalse();
})->with(['../button', '..\\button', '/button', 'C:\\button', 'forms/../../button']);

it('rejects an empty destination setting', function () {
    config(['malevich.components.path' => '']);

    $this->artisan('malevich:add', ['name' => 'button'])
        ->expectsOutputToContain('Set malevich.components.path')->assertFailed();
});

it('does not replace a directory even when forced', function () {
    (new Filesystem)->ensureDirectoryExists($this->kitDestination.'/button/index.blade.php');

    $this->artisan('malevich:add', ['name' => 'button', '--force' => true])
        ->expectsOutputToContain('A directory already exists')->assertFailed();

    expect(is_dir($this->kitDestination.'/button/index.blade.php'))->toBeTrue();
});

it('lets you pick several kit components when no name is given', function () {
    $this->artisan('malevich:add')
        ->expectsChoice('Which kit components do you want to add?', ['button', 'input'], ['avatar', 'badge', 'button', 'button-old', 'button.group', 'checkbox', 'dialog', 'divider', 'field', 'heading', 'input', 'panel', 'progress', 'radio', 'select', 'slider', 'switch', 'tabs', 'tabs.list', 'tabs.panel', 'tabs.tab', 'text'])
        ->expectsOutputToContain('Kit component added.')
        ->assertSuccessful();

    expect($this->kitDestination.'/button/index.blade.php')->toBeFile()
        ->and($this->kitDestination.'/input/index.blade.php')->toBeFile();
});

it('adds several named kit components at once', function () {
    $this->artisan('malevich:add', ['name' => ['button', 'input']])->assertSuccessful();

    expect($this->kitDestination.'/button/index.blade.php')->toBeFile()
        ->and($this->kitDestination.'/input/index.blade.php')->toBeFile();
});

it('adds the components a kit component needs', function () {
    $this->artisan('malevich:add', ['name' => 'input'])
        ->expectsOutputToContain('[field] is required')
        ->assertSuccessful();

    expect($this->kitDestination.'/input/index.blade.php')->toBeFile()
        ->and($this->kitDestination.'/field/index.blade.php')->toBeFile();
});

it('keeps an existing dependency even when forced', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->kitDestination);
    $files->ensureDirectoryExists($this->kitDestination.'/field');
    $files->put($this->kitDestination.'/field/index.blade.php', 'custom field');

    $this->artisan('malevich:add', ['name' => 'input', '--force' => true])
        ->doesntExpectOutputToContain('is required')
        ->assertSuccessful();

    expect(file_get_contents($this->kitDestination.'/field/index.blade.php'))->toBe('custom field')
        ->and($this->kitDestination.'/input/index.blade.php')->toBeFile();
});

it('does not add a dependency twice when it is also named', function () {
    $this->artisan('malevich:add', ['name' => ['input', 'field']])
        ->doesntExpectOutputToContain('is required')
        ->assertSuccessful();
});

it('points to the theme tokens until they are published', function () {
    $this->artisan('malevich:add', ['name' => 'button'])
        ->expectsOutputToContain('vendor:publish --tag=malevich:theme')
        ->assertSuccessful();
});

it('publishes the kit theme tokens', function () {
    $published = resource_path('css/vendor/malevich.css');

    try {
        $this->artisan('vendor:publish', ['--tag' => 'malevich:theme'])->assertSuccessful();

        expect(file_get_contents($published))
            ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/kit/theme.css'))
            ->toContain('@theme inline', '--radius-brand', '--color-brand', '--color-info');

        $this->artisan('malevich:add', ['name' => 'button', '--force' => true])
            ->doesntExpectOutputToContain('vendor:publish --tag=malevich:theme')
            ->assertSuccessful();
    } finally {
        @unlink($published);
    }
});
