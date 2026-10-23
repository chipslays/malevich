<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Playground\Playground;

function kitFiles(): array
{
    return glob(dirname(__DIR__, 2).'/resources/kit/*.blade.php') ?: [];
}

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
});

describe('every component', function () {
    it('renders every value of every option', function (string $file) {
        $name = basename($file, '.blade.php');
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
