<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Malevich\Malevich;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
    Malevich::strict();
});

it('renders a text input inside a styled field', function () {
    $html = $this->render('<x-kit::input name="email" placeholder="you@example.com" />');

    expect($html)
        ->toContain('<div', '<input', 'type="text"', 'name="email"', 'placeholder="you@example.com"')
        ->not->toContain(' variant=')->not->toContain(' size=');

    expect($html)
        ->toHaveClasses(['h-10', 'px-3', 'rounded-brand', 'border-gray-300', 'focus-within:outline-primary-600'])
        ->toHaveClasses(['bg-transparent', 'flex-1', 'outline-none']);
});

it('styles the box with class and style, and passes the rest to the input', function () {
    $html = $this->render('<x-kit::input class="mt-2 w-64" style="color: red" name="q" input:class="font-mono tracking-wide" />');

    expect($html)
        ->toHaveClasses(['rounded-brand', 'mt-2', 'w-64'])
        ->toHaveClasses(['flex-1', 'font-mono', 'tracking-wide'])
        ->not->toContain('input:class')->not->toContain('input-class')
        ->toContain('style="color: red"', 'name="q"');

    expect(substr_count($html, 'mt-2'))->toBe(1);
});

it('keeps an explicit type', function () {
    expect($this->render('<x-kit::input type="email" />'))
        ->toContain('type="email"')->not->toContain('type="text"');
});

it('renders every variant, size and color', function () {
    foreach (['outline', 'soft', 'elevated', 'ghost'] as $variant) {
        foreach (['xs', 'sm', 'md', 'lg', 'xl'] as $size) {
            foreach (['primary' => 'primary', 'neutral' => 'gray', 'danger' => 'danger'] as $color => $scale) {
                expect($this->render('<x-kit::input :variant="$variant" :size="$size" :color="$color" />', compact('variant', 'size', 'color')))
                    ->toContain('<input', "focus-within:outline-{$scale}-600");
            }
        }
    }
});

it('marks invalid and disabled inputs', function () {
    expect($this->render('<x-kit::input invalid />'))->toContain('aria-invalid="true"');
    expect($this->render('<x-kit::input disabled />'))->toContain(' disabled', 'data-disabled=');
    expect($this->render('<x-kit::input />'))->not->toContain(' aria-invalid=')->not->toContain(' disabled ')->not->toContain(' disabled="')->not->toContain(' data-disabled="');
});

it('renders any markup in the leading and trailing slots', function () {
    $html = $this->render(<<<'BLADE'
        <x-kit::input type="password" name="password">
            <x-slot:leading><svg></svg></x-slot:leading>
            <x-slot:trailing class="-me-1">
                <button type="button">Clear</button>
                <button type="button" aria-label="Show password">Eye</button>
            </x-slot:trailing>
        </x-kit::input>
        BLADE);

    expect($html)
        ->toContain('name="password"', '<svg></svg>', '>Clear</button>', 'aria-label="Show password"')
        ->toHaveClasses(['-me-1', 'shrink-0']);
});

describe('label, description and error', function () {
    it('links the label, description and error to the input', function () {
        $html = $this->render('<x-kit::input id="email" name="email" label="Email" description="We never share it." error="Required." required class="mt-4" />');

        expect($html)
            ->toContain('<label', 'for="email"', '>*</span>', 'id="email-description"', 'id="email-error"', 'We never share it.', 'Required.')
            ->toContain('aria-describedby="email-description email-error"', 'aria-invalid="true"', ' required')
            ->toHaveClasses(['flex-col', 'mt-4']);

        expect(substr_count($html, 'mt-4'))->toBe(1);
    });

    it('generates an id when none is given', function () {
        $html = $this->render('<x-kit::input label="Name" />');

        preg_match('/for="([^"]+)"/', $html, $match);

        expect($match[1] ?? null)->toStartWith('input-');
        expect($html)->toContain('id="'.$match[1].'"');
    });

    it('marks invalid without a message for error true', function () {
        expect($this->render('<x-kit::input :error="true" />'))
            ->toContain('aria-invalid="true"')->not->toContain('<p')->not->toContain(' id=');
    });

    it('takes the error from the validation bag by name', function () {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['user.email' => ['Invalid email.']]));

        // Laravel shares $errors with every view, components included.
        View::share('errors', $errors);

        expect($this->render('<x-kit::input name="user[email]" />'))
            ->toContain('Invalid email.', 'aria-invalid="true"');

        expect($this->render('<x-kit::input name="user[email]" :error="false" />'))
            ->not->toContain('Invalid email.');
    });

    it('accepts slots with markup', function () {
        $html = $this->render(<<<'BLADE'
            <x-kit::input>
                <x-slot:label>Password <a href="/forgot">Forgot?</a></x-slot:label>
                <x-slot:description class="italic">At least <strong>8</strong> characters.</x-slot:description>
            </x-kit::input>
            BLADE);

        expect($html)->toContain('<a href="/forgot">', '<strong>8</strong>')->toHaveClasses(['italic']);
    });
});

describe('field', function () {
    it('wraps any control', function () {
        $html = $this->render('<x-kit::field label="Country" for="country" description="For shipping." required><select id="country"></select></x-kit::field>');

        expect($html)->toContain('<label', 'for="country"', '<select id="country">', 'id="country-description"', '>*</span>');
    });

    it('renders a group as a fieldset with a legend', function () {
        $html = $this->render('<x-kit::field as="fieldset" label="Plan" error="Pick one."><input type="radio" /></x-kit::field>');

        expect($html)->toContain('<fieldset', '<legend', 'Pick one.', 'data-invalid', 'aria-describedby="field-')->not->toContain('<label');
    });
});
