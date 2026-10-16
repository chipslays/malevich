<?php

use Malevich\Malevich;
use TailwindMerge\TailwindMerge;

beforeEach(function () {
    require_once __DIR__.'/../fixtures/stubs/TailwindMerge.php';

    TailwindMerge::$prefix = 'merged: ';
    Malevich::flushConfig();
});

afterEach(fn () => TailwindMerge::$prefix = '');

it('uses tailwind-merge when it is installed', function () {
    expect($this->render('<x-button variant="ghost">Hi</x-button>'))->toContain('class="merged: btn btn-ghost"');
});

it('can be turned off in config', function () {
    config(['malevich.merge_classes' => false]);
    Malevich::flushConfig();

    expect($this->render('<x-button variant="ghost">Hi</x-button>'))->toContain('class="btn btn-ghost"');
});

it('lets mergeClassesUsing() win', function () {
    Malevich::mergeClassesUsing(fn (string $classes) => strtoupper($classes));

    expect($this->render('<x-button variant="ghost">Hi</x-button>'))->toContain('class="BTN BTN-GHOST"');
});
