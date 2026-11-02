<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Malevich\Malevich;
use Malevich\Playground\Playground;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'groupkit');
    Malevich::strict();
});

it('groups buttons and links without changing their individual actions', function () {
    $html = $this->render('<x-groupkit::button.group aria-label="Pagination" id="pages"><x-groupkit::button name="page" value="1">Previous</x-groupkit::button><x-groupkit::button href="/next" disabled>Next</x-groupkit::button></x-groupkit::button.group>');

    expect($html)->toContain('role="group"', 'aria-label="Pagination"', 'id="pages"', 'data-orientation="horizontal"', 'rounded-s-none', 'rounded-e-none', 'name="page"', 'value="1"', 'Previous', 'Next', 'aria-disabled="true"')
        ->not->toContain('href="/next"', 'rounded-t-none');
});

it('supports a vertical group and full width without stretching button heights', function () {
    $html = $this->render('<x-groupkit::button.group orientation="vertical" block><x-groupkit::button>One</x-groupkit::button><x-groupkit::button>Two</x-groupkit::button></x-groupkit::button.group>');

    expect($html)->toContain('flex-col', 'w-full', 'data-orientation="vertical"', 'rounded-t-none', 'rounded-b-none', '-mt-px')
        ->not->toContain('rounded-s-none', ']:flex-1');
});

it('distributes full width horizontal groups and keeps a single button rounded', function () {
    $html = $this->render('<x-groupkit::button.group block><x-groupkit::button radius="full">Only</x-groupkit::button></x-groupkit::button.group>');

    expect($html)->toContain('w-full', ']:flex-1', 'rounded-full', ':not(:first-child)', ':not(:last-child)');
});

it('provides a playground example', function () {
    expect(Playground::make()->example(dirname(__DIR__, 2).'/resources/kit/button/group.blade.php'))->not->toBeNull();
    expect(Playground::make()->components())->toHaveKeys(['groupkit::button', 'groupkit::button.group']);
});

it('installs the group together with its buttons', function () {
    $destination = sys_get_temp_dir().'/malevich-button.group-'.uniqid();
    config(['malevich.components.path' => $destination]);

    try {
        $this->artisan('malevich:add', ['name' => 'button.group'])
            ->expectsOutputToContain('[button] is required')->assertSuccessful();

        expect($destination.'/button/group.blade.php')->toBeFile()
            ->and($destination.'/button/index.blade.php')->toBeFile();

        Blade::anonymousComponentPath($destination, 'installedgroup');

        expect($this->render('<x-installedgroup::button.group><x-installedgroup::button>Save</x-installedgroup::button></x-installedgroup::button.group>'))
            ->toContain('role="group"', 'tone-brand', 'Save');
    } finally {
        (new Filesystem)->deleteDirectory($destination);
    }
});
