<?php

beforeEach(fn () => config(['malevich.default_target' => '_']));

it('uses a custom name for the main element in @cases', function () {
    expect($this->render('<x-cased-root>Hi</x-cased-root>'))
        ->toContain('<div class="p-4 bg-teal">')
        ->toContain('<span class="text-teal-100">Hi</span>');
});

it('keeps plain values on the main element', function () {
    expect($this->render('<x-cased-root variant="plain">Hi</x-cased-root>'))->toContain('<div class="p-4 bg-plain">');
});

it('uses the custom name in per-target values', function () {
    expect($this->render('<x-cased-root :size="[\'_\' => \'sm\']">Hi</x-cased-root>'))->toContain('<div class="p-2 bg-teal">');
});
