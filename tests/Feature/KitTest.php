<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\DynamicComponent;
use Malevich\Playground\Playground;

function kitFiles(): array
{
    return array_values(array_map(
        fn ($file) => $file->getPathname(),
        array_filter((new Filesystem)->allFiles(dirname(__DIR__, 2).'/resources/kit'),
            fn ($file) => str_ends_with($file->getFilename(), '.blade.php') && ! str_ends_with($file->getFilename(), '-old.blade.php')),
    ));
}

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
    config(['malevich.components.prefix' => 'kit']);
    (new ReflectionProperty(DynamicComponent::class, 'compiler'))->setValue(null, null);
});

describe('every component', function () {
    it('renders every value of every option', function (string $file) {
        $relative = str_replace('\\', '/', substr($file, strlen(dirname(__DIR__, 2).'/resources/kit/')));
        $name = str_replace('/', '.', preg_replace('#(?:/index)?\.blade\.php$#', '', $relative));
        $component = Playground::make()->components()['kit::'.$name] ?? null;

        expect($component)->not->toBeNull();

        foreach ($component['options'] as $option => $values) {
            foreach ($values as $value) {
                $binding = in_array($value, ['true', 'false'], true) ? $value : "'{$value}'";

                expect($this->render("<x-kit::{$name} :{$option}=\"{$binding}\">Text</x-kit::{$name}>"))
                    ->toContain('class="');
            }
        }
    })->with(fn () => kitFiles());

    it('has a usage example for the playground', function (string $file) {
        expect(Playground::make()->example($file))->not->toBeNull();
    })->with(fn () => kitFiles());
});

describe('button', function () {
    it('is a button, a link with href, and busy while loading', function () {
        expect($this->render('<x-kit::button>Save</x-kit::button>'))
            ->toContain('<button')->toContain('type="button"');

        expect($this->render('<x-kit::button href="/pricing">Pricing</x-kit::button>'))
            ->toContain('<a')->toContain('href="/pricing"');

        expect($this->render('<x-kit::button loading>Saving</x-kit::button>'))
            ->toContain('aria-busy="true"')->toContain('animate-spin')->toContain('pointer-events-none');
    });
});
