<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Malevich\Malevich;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'composition');
    Malevich::strict();
});

it('derives Unicode initials and supports an explicit fallback', function () {
    expect($this->render('<x-composition::avatar name="Алексей Игоревич Морозов" />'))
        ->toContain('АМ', 'data-state="fallback"', 'aria-label="Алексей Игоревич Морозов"')->not->toContain('<img');
    expect($this->render('<x-composition::avatar name="Alex Morgan" initials="JD" />'))->toContain('JD');
    expect($this->render('<x-composition::avatar alt="Guest"><b>Guest</b></x-composition::avatar>'))->toContain('<b>Guest</b>', 'aria-label="Guest"');
});

it('renders a stable image layer over the fallback while it loads', function () {
    $html = $this->render('<x-composition::avatar src="/portrait.jpg" srcset="/portrait@2x.jpg 2x" name="Alex Morgan" loading="eager" nonce="abc" image:class="object-top" status="online" />');
    expect($html)->toContain('data-state="loading"', 'AM', 'src="/portrait.jpg"', 'srcset="/portrait@2x.jpg 2x"', 'loading="eager"', 'decoding="async"', 'object-top', 'bg-success', 'nonce="abc"')
        ->not->toContain('image:class=');
});

it('uses exactly the button radius vocabulary for avatars', function () {
    foreach (['brand', 'none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', 'full', 'squircle'] as $radius) {
        expect($this->render('<x-composition::avatar name="Alex Morgan" :radius="$radius" />', compact('radius')))
            ->toContain($radius === 'squircle' ? '[corner-shape:squircle]' : 'rounded-'.$radius);
    }
});

it('installs a complete tabs family from either the root or a subcomponent', function (string $name) {
    $destination = sys_get_temp_dir().'/malevich-tabs-'.uniqid();
    config(['malevich.components.path' => $destination]);

    try {
        $this->artisan('malevich:add', ['name' => $name])->assertSuccessful();
        foreach (['index', 'list', 'tab', 'panel'] as $file) {
            expect($destination.'/tabs/'.$file.'.blade.php')->toBeFile();
        }
    } finally {
        (new Filesystem)->deleteDirectory($destination);
    }
})->with(['tabs', 'tabs.tab', 'tabs/index.blade.php']);

it('renders tab roles and links the selected panels in one event bridge', function () {
    $html = $this->render('<x-composition::tabs value="two"><x-composition::tabs.list aria-label="Sections"><x-composition::tabs.tab value="one">One</x-composition::tabs.tab><x-composition::tabs.tab value="two">Two</x-composition::tabs.tab><x-composition::tabs.tab value="three" disabled>Three</x-composition::tabs.tab></x-composition::tabs.list><x-composition::tabs.panel value="one">First panel</x-composition::tabs.panel><x-composition::tabs.panel value="two">Second panel</x-composition::tabs.panel></x-composition::tabs>');
    expect($html)->toContain('role="tablist"', 'role="tab"', 'role="tabpanel"', 'data-value="two"', 'aria-selected="false"', 'disabled', 'First panel', 'Second panel', "'tabs:change'", "'invalid'");
});

it('keeps heading semantics independent from visual size and defaults text to 14px', function () {
    expect($this->render('<x-composition::heading as="h1" size="sm" id="title">Title</x-composition::heading>'))
        ->toContain('<h1', 'text-lg', 'id="title"', 'Title');
    expect($this->render('<x-composition::text color="neutral">Description</x-composition::text>'))
        ->toContain('<p', 'text-sm', 'tone-neutral')->not->toContain('text-base');
});
