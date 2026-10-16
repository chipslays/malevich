<?php

use Illuminate\Support\Facades\Blade;

$starters = glob(__DIR__.'/../../resources/stubs/components/*.blade.php');

beforeEach(fn () => Blade::anonymousComponentPath(__DIR__.'/../../resources/stubs/components', 'starter'));

it('compiles every starter component', function (string $file) {
    expect(Blade::compileString(file_get_contents($file)))->toBeString();
})->with($starters);

it('renders the stat component as documented', function () {
    $teal = $this->render('<x-starter::stat variant="teal" title="Meters" :value="12" unit="pcs">18 devices</x-starter::stat>');
    $graphite = $this->render('<x-starter::stat variant="graphite" title="Documents" :value="67">+2</x-starter::stat>');

    expect($teal)
        ->toContain('from-teal-600 to-teal-800')
        ->toContain('text-teal-100')
        ->toContain('bg-teal-300/30')
        ->and($graphite)
        ->toContain('from-gray-800 to-gray-950')
        ->not->toContain('bg-teal-300/30');
});

it('lets a preset win over the card component defaults', function () {
    expect($this->render('<x-starter::card>Hi</x-starter::card>'))
        ->toContain('border border-zinc-200 p-6')
        ->and($this->render('<x-starter::card preset="featured">Hi</x-starter::card>'))
        ->toContain('shadow-xl')->toContain('p-10')->not->toContain('border-zinc-200')
        ->and($this->render('<x-starter::card preset="featured" padding="sm">Hi</x-starter::card>'))
        ->toContain('p-3')->not->toContain('preset=');
});
