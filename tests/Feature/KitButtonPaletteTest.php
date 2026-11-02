<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Malevich;

it('renders the shared variant recipes with each semantic palette', function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'palette');
    Malevich::strict();

    foreach (['brand', 'neutral', 'contrast', 'success', 'info', 'warning', 'danger'] as $color) {
        foreach (['solid', 'soft', 'surface', 'elevated', 'outline', 'ghost', 'link'] as $variant) {
            $html = $this->render('<x-palette::button :color="$color" :variant="$variant">Save</x-palette::button>', compact('color', 'variant'));

            expect($html)->toContain('button-colors', 'tone-'.$color, 'Save')
                ->not->toContain(' color=', ' variant=', 'style=');

            expect($html)->toContain(match ($variant) {
                'solid' => 'bg-(--button-solid)',
                'soft' => 'bg-(--button-soft)',
                'surface' => 'bg-(--button-surface)',
                'elevated' => 'shadow-xs',
                'outline' => 'hover:bg-(--button-soft)',
                'ghost' => 'hover:bg-(--button-soft)',
                'link' => 'hover:text-(--button-text-hover)',
            });
        }
    }
});

it('keeps loading buttons readable and detects icon-only buttons', function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'palette');
    expect($this->render('<x-palette::button loading>Save</x-palette::button>'))
        ->toContain('Save', 'animate-spin', 'aria-busy="true"', 'disabled="disabled"')->not->toContain('opacity-0');
    expect($this->render('<x-palette::button loading><x-slot:loadingLabel>Saving</x-slot:loadingLabel> Save</x-palette::button>'))
        ->toContain('Saving', 'opacity-0', 'absolute inset-0');
    expect($this->render('<x-palette::button aria-label="Add"><svg></svg></x-palette::button>'))
        ->toContain('aspect-square', 'size-9')->not->toContain('px-3.5');
});
