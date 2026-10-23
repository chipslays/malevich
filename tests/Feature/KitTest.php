<?php

use Illuminate\Support\Facades\Blade;
use Malevich\Catalog\Catalog;

function kitFiles(): array
{
    return glob(dirname(__DIR__, 2).'/resources/kit/*.blade.php') ?: [];
}

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
});

describe('every component', function () {
    it('renders every value of every option', function (string $file) {
        $name = basename($file, '.blade.php');
        $component = Catalog::make()->components()['kit::'.$name] ?? null;

        expect($component)->not->toBeNull();

        foreach ($component['options'] as $option => $values) {
            foreach ($values as $value) {
                $binding = in_array($value, ['true', 'false'], true) ? $value : "'{$value}'";

                expect($this->render("<x-kit::{$name} :{$option}=\"{$binding}\">Text</x-kit::{$name}>"))
                    ->toContain('class="');
            }
        }
    })->with(fn () => kitFiles());

    it('has a usage example for the catalog', function (string $file) {
        expect(Catalog::make()->example($file))->not->toBeNull();
    })->with(fn () => kitFiles());
});

describe('button', function () {
    it('is a button, a link with href, and busy while loading', function () {
        expect($this->render('<x-kit::button>Save</x-kit::button>'))
            ->toContain('<button')->toContain('type="button"');

        expect($this->render('<x-kit::button href="/pricing">Pricing</x-kit::button>'))
            ->toContain('<a')->toContain('href="/pricing"');

        expect($this->render('<x-kit::button loading>Saving</x-kit::button>'))
            ->toContain('aria-busy="true"')->toContain('animate-spin')->toContain('pointer-events-none');
    });
});

describe('badge', function () {
    it('draws a dot only when asked', function () {
        expect($this->render('<x-kit::badge>Draft</x-kit::badge>'))->not->toContain('bg-current');
        expect($this->render('<x-kit::badge dot>Live</x-kit::badge>'))->toContain('bg-current');
    });
});

describe('alert', function () {
    it('has a role, a title from a slot or a prop, and an optional icon', function () {
        expect($this->render('<x-kit::alert variant="danger"><x-slot:title class="text-lg">Failed</x-slot:title> Declined.</x-kit::alert>'))
            ->toContain('role="alert"')
            ->toContain('<svg')
            ->toMatch('/<p class="[^"]*font-semibold[^"]* text-lg">Failed<\/p>/');

        expect($this->render('<x-kit::alert title="Saved" :icon="false" />'))
            ->toContain('Saved')
            ->not->toContain('<svg');
    });
});

describe('card', function () {
    it('draws the header and the footer only when they are given', function () {
        expect($this->render('<x-kit::card>Body</x-kit::card>'))
            ->not->toContain('<h3')->toContain('Body');

        expect($this->render('<x-kit::card title="Billing" description="Your plan"><x-slot:footer>Done</x-slot:footer> Body</x-kit::card>'))
            ->toContain('>Billing</h3>')
            ->toContain('>Your plan</p>')
            ->toContain('>Done</div>');
    });
});

describe('avatar', function () {
    it('shows the image, the initials or a placeholder', function () {
        expect($this->render('<x-kit::avatar src="/ada.jpg" name="Ada Lovelace" />'))
            ->toContain('src="/ada.jpg"')->toContain('alt="Ada Lovelace"');

        expect($this->render('<x-kit::avatar name="Ada King Lovelace" />'))
            ->toContain('> AK </span>');

        expect($this->render('<x-kit::avatar />'))->toContain('<svg');
    });
});

describe('spinner', function () {
    it('tells screen readers what it is', function () {
        expect($this->render('<x-kit::spinner label="Saving" />'))
            ->toContain('role="status"')->toContain('aria-label="Saving"');
    });
});

describe('form controls', function () {
    it('are invalid when the form has an error for their name', function (string $control) {
        $this->withViewErrors(['email' => 'Required', 'items.0' => 'Required']);

        expect($this->render("<x-kit::{$control} name=\"email\" />"))->toContain('aria-invalid="true"');
        expect($this->render("<x-kit::{$control} name=\"items[0]\" />"))->toContain('aria-invalid="true"');
        expect($this->render("<x-kit::{$control} name=\"name\" />"))->not->toContain('aria-invalid="');
        expect($this->render("<x-kit::{$control} name=\"name\" invalid />"))->toContain('aria-invalid="true"');
    })->with(['input', 'textarea', 'select']);

    it('renders the select options and picks the value', function () {
        $html = $this->render('<x-kit::select placeholder="Plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" value="pro" />');

        expect($html)
            ->toContain('<option value="" disabled >Plan</option>')
            ->toContain('<option value="free" >Free</option>')
            ->toContain('<option value="pro" selected>Pro</option>');
    });

    it('puts the attributes of a checkbox, radio and switch on the input', function (string $control, string $type) {
        expect($this->render("<x-kit::{$control} name=\"terms\" checked>Agree</x-kit::{$control}>"))
            ->toMatch("/<input class=\"[^\"]*peer[^\"]*\" name=\"terms\" checked=\"checked\" type=\"{$type}\"/")
            ->toContain('Agree');
    })->with([
        ['checkbox', 'checkbox'],
        ['radio', 'radio'],
        ['switch', 'checkbox'],
    ]);
});

describe('field', function () {
    it('shows the label, the hint, or the error in its place', function () {
        expect($this->render('<x-kit::field label="Email" for="email" hint="Never shared" required>Input</x-kit::field>'))
            ->toContain('for="email"')
            ->toContain("after:content-['*']")
            ->toContain('Never shared');

        $this->withViewErrors(['email' => 'The email is required.']);

        expect($this->render('<x-kit::field label="Email" name="email" hint="Never shared">Input</x-kit::field>'))
            ->toContain('The email is required.')
            ->not->toContain('Never shared');
    });
});
