<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->path = sys_get_temp_dir().'/malevich-'.uniqid();
    config(['malevich.components.path' => $this->path]);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->path);
});

it('creates a component with every configured directive', function () {
    config(['malevich.directives' => ['variant', 'size', 'color', 'radius']]);

    $this->artisan('make:malevich', ['name' => 'button'])->assertSuccessful();

    $contents = file_get_contents($this->path.'/button.blade.php');

    expect($contents)
        ->toContain("'variant' => null,", "'radius' => null,", "@base('')", '@color([', '@radius([', '<div @ui>')
        ->not->toContain('{{ props }}', '{{ directives }}', '{{ render }}');
});

it('creates nested components and accepts the .blade.php suffix', function () {
    $this->artisan('make:malevich', ['name' => 'forms/input.blade.php'])->assertSuccessful();

    expect($this->path.'/forms/input.blade.php')->toBeFile();
});

it('refuses to overwrite unless forced', function () {
    $this->artisan('make:malevich', ['name' => 'badge'])->assertSuccessful();
    $this->artisan('make:malevich', ['name' => 'badge'])->assertFailed();
    $this->artisan('make:malevich', ['name' => 'badge', '--force' => true])->assertSuccessful();
});

it('generates a component that renders', function () {
    $this->artisan('make:malevich', ['name' => 'box'])->assertSuccessful();
    Blade::anonymousComponentPath($this->path);

    expect($this->render('<x-box class="p-1">Hi</x-box>'))->toBe('<div class="p-1"> Hi </div>');
});
