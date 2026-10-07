<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->published = resource_path('views/vendor/malevich/components');
});

afterEach(function () {
    File::deleteDirectory(resource_path('views/vendor/malevich'));
});

it('publishes the components into resources/views/vendor/malevich', function () {
    $this->artisan('vendor:publish', ['--tag' => 'malevich:components'])->assertSuccessful();

    expect($this->published.'/primitive.blade.php')->toBeFile();
});

it('prefers a published copy over the package component', function () {
    File::ensureDirectoryExists($this->published);
    File::put($this->published.'/primitive.blade.php', '<p {{ $attributes }}>custom {{ $slot }}</p>');

    $this->refreshApplication();
    $this->setUp();

    expect($this->render('<x-malevich::primitive id="x">hi</x-malevich::primitive>'))->toBe('<p id="x">custom hi</p>');
});

it('never resolves unprefixed tags to package components', function () {
    // There is no primitive fixture, so <x-primitive> must not find malevich::primitive.
    $this->render('<x-primitive />');
})->throws(InvalidArgumentException::class, 'Unable to locate a class or view for component [primitive]');
