<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Malevich;
use Malevich\Playground\Playground;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
    Malevich::strict();
});

it('renders a native button and forwards attributes without leaking recipe options', function () {
    $html = $this->render('<x-kit::button type="submit" name="action" value="save" id="save" class="mt-2" wire:click="save" size="md" radius="lg">Save</x-kit::button>');
    expect($html)->toContain('<button', 'type="submit"', 'name="action"', 'value="save"', 'id="save"', 'mt-2', 'wire:click="save"', 'h-9', 'text-sm', 'rounded-lg', 'tone-brand')
        ->not->toContain(' variant=', ' color=', ' size=', ' radius=');
});

it('renders links and keeps disabled or loading links from navigating', function () {
    expect($this->render('<x-kit::button href="/docs" target="_blank">Docs</x-kit::button>'))
        ->toContain('<a', 'href="/docs"', 'target="_blank"', 'noopener');
    foreach (['disabled', 'loading'] as $state) {
        expect($this->render('<x-kit::button href="/docs" '.$state.'>Docs</x-kit::button>'))
            ->toContain('<a', 'aria-disabled="true"', 'data-disabled')->not->toContain('href="/docs"');
    }
});

it('supports an explicit element and native disabled state', function () {
    expect($this->render('<x-kit::button as="span">Label</x-kit::button>'))->toContain('<span');
    expect($this->render('<x-kit::button disabled>Save</x-kit::button>'))->toContain('disabled="disabled"', 'data-disabled');
    expect($this->render('<x-kit::button disabled="false" loading="false">Save</x-kit::button>'))->not->toContain('disabled="disabled"', 'animate-spin');
});

it('matches the shared control heights at every size', function (string $size, string $height) {
    expect($this->render('<x-kit::button :size="$size">Save</x-kit::button>', compact('size')))->toHaveClasses($height);
})->with([['xs', 'h-7'], ['sm', 'h-8'], ['md', 'h-9'], ['lg', 'h-10'], ['xl', 'h-11']]);

it('supports the complete radius scale', function (string $radius) {
    expect($this->render('<x-kit::button :radius="$radius">Save</x-kit::button>', compact('radius')))
        ->toContain($radius === 'squircle' ? '[corner-shape:squircle]' : 'rounded-'.$radius);
})->with(['brand', 'none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', 'full', 'squircle']);

it('makes buttons with no visible text square', function (string $content) {
    expect($this->render('<x-kit::button aria-label="Add">'.$content.'</x-kit::button>'))
        ->toHaveClasses(['aspect-square', 'size-9'])->not->toContain('px-3.5');
})->with(['', '   ', '<!-- decorative -->', '<svg viewBox="0 0 24 24"><path d="M4 12h16" /></svg>']);

it('keeps text buttons rectangular and accepts explicit square overrides', function () {
    expect($this->render('<x-kit::button><strong>Save</strong></x-kit::button>'))->toHaveClasses('px-3.5')->not->toContain('aspect-square');
    expect($this->render('<x-kit::button square>Save</x-kit::button>'))->toHaveClasses('size-9');
    expect($this->render('<x-kit::button :square="false"><svg></svg></x-kit::button>'))->toHaveClasses('px-3.5')->not->toContain('aspect-square');
});

it('keeps slot classes and independent icon sizes on the correct elements', function () {
    $html = $this->render('<x-kit::button class="mt-2" leading:size="xs" trailing:size="lg"><x-slot:leading class="text-info"><svg></svg></x-slot:leading> Save<x-slot:trailing data-part="end"><svg></svg></x-slot:trailing></x-kit::button>');
    expect($html)->toContain('[&>svg]:size-3', '[&>svg]:size-4.5', 'text-info', 'data-part="end"')->not->toContain('leading:size=', 'trailing:size=');
    expect(substr_count($html, 'mt-2'))->toBe(1);
});

it('preserves the caption during ordinary loading and replaces the leading icon', function () {
    $html = $this->render('<x-kit::button loading :disabled="false"><x-slot:leading>Old icon</x-slot:leading> Save<x-slot:trailing>End</x-slot:trailing></x-kit::button>');
    expect($html)->toContain('Save', 'End', 'animate-spin', 'aria-busy="true"', 'disabled="disabled"')
        ->not->toContain('Old icon', 'opacity-0');
});

it('overlays rich loading content without removing the original layout content', function () {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel class="font-semibold"><strong>Saving</strong> changes</x-slot:loadingLabel> Save changes</x-kit::button>');
    expect($html)->toContain('Save changes', '<strong>Saving</strong>', 'font-semibold', 'opacity-0', 'absolute inset-0', 'aria-hidden="true"');
    expect($this->render('<x-kit::button><x-slot:loadingLabel>Saving</x-slot:loadingLabel> Save</x-kit::button>'))->not->toContain('Saving', 'animate-spin');
});

it('supports spinner-only loading while preserving the original caption', function () {
    expect($this->render('<x-kit::button loading><x-slot:loadingLabel></x-slot:loadingLabel> Save</x-kit::button>'))
        ->toContain('Save', 'opacity-0', 'animate-spin');
    expect($this->render('<x-kit::button loading aria-label="Add"><svg></svg><x-slot:loadingLabel>Adding</x-slot:loadingLabel></x-kit::button>'))
        ->toContain('size-9', 'aria-label="Add"', 'animate-spin')->not->toContain('Adding');
});

it('stretches block buttons without affecting ordinary buttons', function () {
    expect($this->render('<x-kit::button block>Continue</x-kit::button>'))->toHaveClasses('w-full');
    expect($this->render('<x-kit::button block="false">Continue</x-kit::button>'))->not->toContain('w-full');
});

it('is discoverable with the current semantic palette and a playground example', function () {
    $playground = Playground::make();
    $component = $playground->components()['kit::button'];
    expect($component['options']['color'])->toBe(['brand', 'neutral', 'contrast', 'success', 'info', 'warning', 'danger']);
    expect($playground->example($component['file']))->not->toBeNull();
    expect($playground->sheet('kit::button', $component)['default']->error)->toBeNull();
});
