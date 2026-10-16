<?php

it('adds no data attributes by default', function () {
    expect($this->render('<x-button variant="ghost">Hi</x-button>'))->not->toContain('data-variant');
});

describe('with data attributes enabled', function () {
    beforeEach(fn () => config(['malevich.data_attributes' => true]));

    it('puts the value of every option on the main element', function () {
        expect($this->render('<x-button variant="ghost" size="lg" icon>Hi</x-button>'))
            ->toContain('<button class="btn btn-ghost" type="button" data-variant="ghost" data-size="lg">')
            ->toContain('<svg class="w-6"></svg>');
    });

    it('leaves out options without a value', function () {
        expect($this->render('<x-button>Hi</x-button>'))->toContain('<button class="btn" type="button">');
    });

    it('writes a true flag without a value and leaves a false one out', function () {
        expect($this->render('<x-toggle checked />'))->toBe('<span class="bg-green-500" data-checked=""></span>')
            ->and($this->render('<x-toggle />'))->toBe('<span class="bg-gray-200"></span>');
    });

    it('lets an explicit attribute win', function () {
        expect($this->render('<x-button variant="ghost" data-variant="custom">Hi</x-button>'))
            ->toContain('data-variant="custom"')
            ->not->toContain('data-variant="ghost"');
    });

    it('escapes the values', function () {
        expect($this->render('<x-button :variant="$v">Hi</x-button>', ['v' => '"><b>']))
            ->toContain('data-variant="&quot;&gt;&lt;b&gt;"');
    });
});
