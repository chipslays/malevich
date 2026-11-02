<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\DynamicComponent;
use Malevich\Malevich;

beforeEach(function () {
    // Laravel keeps the dynamic tag compiler static across rebuilt test apps.
    (new ReflectionProperty(DynamicComponent::class, 'compiler'))->setValue(null, null);
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'controls');
    config(['malevich.components.prefix' => 'controls']);
    Malevich::strict();
});

it('renders the complete palette across the supported control treatments', function (string $component, array $variants) {
    foreach ($variants as $variant) {
        foreach (['brand', 'neutral', 'contrast', 'success', 'info', 'warning', 'danger'] as $color) {
            foreach (['sm', 'md', 'lg'] as $size) {
                $html = $this->render("<x-controls::{$component} :variant=\"\$variant\" :color=\"\$color\" :size=\"\$size\" />", compact('variant', 'color', 'size'));

                expect($html)->toContain('tone-'.$color)->not->toContain(' variant=', ' color=', ' size="'.$size.'"');
            }
        }
    }
})->with([
    ['input', ['outline', 'soft', 'surface', 'ghost']],
    ['select', ['outline', 'soft', 'surface', 'ghost']],
    ['checkbox', ['solid', 'soft']],
    ['radio', ['solid', 'soft']],
    ['switch', ['solid', 'soft']],
    ['badge', ['solid', 'soft', 'surface', 'outline', 'ghost']],
    ['progress', ['solid', 'soft', 'striped']],
    ['slider', ['solid', 'soft']],
]);

it('routes native attributes to the input and links labels, hints and validation', function () {
    $html = $this->render('<x-controls::input id="email" name="email" type="email" value="a@example.com" label="Email" description="Work address" error="Try another address" required class="w-64" field:class="mt-4" control:class="font-mono" wire:model="email" aria-describedby="external" />');

    expect($html)->toContain('for="email"', 'id="email-description"', 'id="email-error"', 'aria-describedby="external email-description email-error"', 'aria-invalid="true"');
    preg_match('/<input ([^>]+)>/', $html, $control);
    expect($control[1])->toContain('name="email"', 'type="email"', 'value="a@example.com"', 'wire:model="email"', 'font-mono', 'required')
        ->not->toContain('w-64', 'control:class', 'field:class', 'mt-4');
    expect(substr_count($html, 'w-64'))->toBe(1);
    expect(substr_count($html, 'mt-4'))->toBe(1);
});

it('reads dotted validation keys without losing the submitted array name', function () {
    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['user.email' => 'Invalid email'])));

    expect($this->render('<x-controls::input name="user[email]" label="Email" />'))
        ->toContain('Invalid email', 'aria-invalid="true"', 'name="user[email]"');
});

it('keeps a readonly input enabled and preserves rich adornment slots', function () {
    $html = $this->render('<x-controls::input name="amount" readonly><x-slot:leading><b>$</b></x-slot:leading><x-slot:trailing><button type="button">Clear</button></x-slot:trailing></x-controls::input>');

    expect($html)->toContain('readonly', '<b>$</b>', '>Clear</button>')->not->toContain('disabled="disabled"');
});

it('renders select options, groups and multiple selected values safely', function () {
    $html = $this->render('<x-controls::select name="countries[]" multiple :value="[\'ru\', \'us\']" :options="$options" />', ['options' => ['Europe' => ['ru' => 'Russia', 'fr' => 'France'], 'us' => '<United States>']]);

    expect($html)->toContain('name="countries[]"', 'multiple', '<optgroup label="Europe">', '&lt;United States&gt;')
        ->not->toContain('<svg');
    expect(preg_match('/<option value="ru"[^>]*selected/', $html))->toBe(1);
    expect(preg_match('/<option value="us"[^>]*selected/', $html))->toBe(1);
});

it('supports native select slots and a required placeholder', function () {
    $html = $this->render('<x-controls::select name="country" placeholder="Choose" required><option value="ru">Russia</option></x-controls::select>');
    expect($html)->toContain('value="" selected disabled', '<option value="ru">Russia</option>', '<svg');
});

it('keeps choice controls native, labelled and independent', function (string $component, string $type) {
    $html = $this->render("<x-controls::{$component} id=\"agree\" name=\"plan\" value=\"pro\" label=\"Professional\" description=\"For teams\" checked disabled required wire:model=\"plan\" />");
    expect($html)->toContain('type="'.$type.'"', 'name="plan"', 'value="pro"', 'checked', 'disabled', 'required', 'for="agree"', 'wire:model="plan"', 'id="agree-description"');

    if ($component === 'switch') {
        expect($html)->toContain('role="switch"');
    }
})->with([['checkbox', 'checkbox'], ['radio', 'radio'], ['switch', 'checkbox']]);

it('initializes a mixed checkbox using the native indeterminate property', function () {
    expect($this->render('<x-controls::checkbox indeterminate label="Select all" nonce="test-nonce" />'))
        ->toContain('control.indeterminate = true', 'nonce="test-nonce"')->not->toContain('indeterminate="');
});

it('supports a semantic fieldset without inventing a control wrapper API', function () {
    $html = $this->render('<x-controls::field as="fieldset" label="Plan" description="Choose one" disabled><x-controls::radio name="plan" value="basic">Basic</x-controls::radio><x-controls::radio name="plan" value="pro">Pro</x-controls::radio></x-controls::field>');
    expect($html)->toContain('<fieldset', '<legend', 'Plan', 'Basic', 'Pro', 'disabled')->not->toContain('<label for="Plan"');
});

it('clamps progress values and omits value for indeterminate progress', function () {
    expect($this->render('<x-controls::progress :value="120" :max="80" label="Upload" show-value />'))
        ->toContain('max="80"', 'value="80"', '100%');
    $html = $this->render('<x-controls::progress indeterminate aria-label="Loading" />');
    expect($html)->toContain('<progress', 'aria-label="Loading"')->not->toContain(' value=');
});

it('normalizes slider bounds and steps while preserving model bindings and reset support', function () {
    $html = $this->render('<x-controls::slider id="volume" name="volume" :min="-20" :max="0" :step="5" :value="-13" show-value suffix="dB" wire:model="volume" />');
    expect($html)->toContain('type="range"', 'min="-20"', 'max="0"', 'step="5"', 'value="-15"', '--slider-fill:25%', 'wire:model="volume"', 'id="volume-value"', '-15dB', "addEventListener('reset'");
});

it('includes one slider event bridge for multiple sliders and gives them unique ids', function () {
    $html = $this->render('<x-controls::slider show-value /><x-controls::slider show-value />');
    expect(substr_count($html, "addEventListener('input'"))->toBe(1);
    preg_match_all('/<input [^>]*id="([^"]+)"/', $html, $ids);
    expect($ids[1])->toHaveCount(2)->and($ids[1][0])->not->toBe($ids[1][1]);
});

it('keeps fractional slider values on valid steps at the upper boundary', function () {
    expect($this->render('<x-controls::slider :min="0.1" :max="0.3" :step="0.1" :value="0.3" />'))
        ->toContain('value="0.3"', '--slider-fill:100%');
    expect($this->render('<x-controls::slider :min="0" :max="9" :step="5" :value="9" />'))
        ->toContain('value="5"');
});

it('renders dividers with or without text in either orientation', function () {
    foreach (['horizontal', 'vertical'] as $orientation) {
        foreach (['start', 'center', 'end'] as $align) {
            foreach (['solid', 'dashed', 'dotted'] as $variant) {
                $html = $this->render('<x-controls::divider :orientation="$orientation" :align="$align" :variant="$variant">Or</x-controls::divider>', compact('orientation', 'align', 'variant'));
                expect($html)->toContain('role="separator"', 'aria-orientation="'.$orientation.'"', 'Or', 'border-'.$variant);
            }
        }
    }
    expect($this->render('<x-controls::divider />'))->toContain('role="separator"')->not->toContain('font-medium');
});

it('adds field dependencies and renders installed controls with a custom prefix', function () {
    $destination = sys_get_temp_dir().'/malevich-controls-'.uniqid();
    config(['malevich.components.path' => $destination, 'malevich.components.prefix' => 'installed']);

    try {
        $this->artisan('malevich:add', ['name' => ['input', 'select', 'slider']])->assertSuccessful();
        expect($destination.'/field/index.blade.php')->toBeFile();
        Blade::anonymousComponentPath($destination, 'installed');
        (new ReflectionProperty(DynamicComponent::class, 'compiler'))->setValue(null, null);
        expect($this->render('<x-installed::input label="Email" name="email" />'))->toContain('<input', 'Email');
        expect($this->render('<x-installed::select label="Country" />'))->toContain('<select', 'Country');
        expect($this->render('<x-installed::slider label="Volume" />'))->toContain('type="range"', 'Volume');
    } finally {
        (new Filesystem)->deleteDirectory($destination);
    }
});
