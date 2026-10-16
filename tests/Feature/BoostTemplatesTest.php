<?php

use Illuminate\Support\Facades\Blade;

$templates = glob(__DIR__.'/../../resources/boost/skills/malevich-development/templates/*.blade.php');

it('compiles every boost template', function (string $file) {
    expect(Blade::compileString(file_get_contents($file)))->toBeString();
})->with($templates);

it('renders the stat template as documented', function () {
    Blade::anonymousComponentPath(__DIR__.'/../../resources/boost/skills/malevich-development', 'boost');

    $teal = $this->render('<x-boost::templates.stat variant="teal" title="Meters" :value="12" unit="pcs">18 devices</x-boost::templates.stat>');
    $graphite = $this->render('<x-boost::templates.stat variant="graphite" title="Documents" :value="67">+2</x-boost::templates.stat>');

    expect($teal)
        ->toContain('from-teal-600 to-teal-800')
        ->toContain('text-teal-100')
        ->toContain('bg-teal-300/30')
        ->and($graphite)
        ->toContain('from-gray-800 to-gray-950')
        ->not->toContain('bg-teal-300/30');
});

it('lets a preset win over the card template defaults', function () {
    Blade::anonymousComponentPath(__DIR__.'/../../resources/boost/skills/malevich-development', 'boost');

    expect($this->render('<x-boost::templates.card>Hi</x-boost::templates.card>'))
        ->toContain('border border-zinc-200 p-6')
        ->and($this->render('<x-boost::templates.card preset="featured">Hi</x-boost::templates.card>'))
        ->toContain('shadow-xl')->toContain('p-10')->not->toContain('border-zinc-200')
        ->and($this->render('<x-boost::templates.card preset="featured" padding="sm">Hi</x-boost::templates.card>'))
        ->toContain('p-3')->not->toContain('preset=');
});
