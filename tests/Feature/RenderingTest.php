<?php

use Illuminate\Support\Facades\View;

enum Tone: string
{
    case Danger = 'danger';
}

it('resolves axis values straight from @props, no chaining required', function () {
    expect($this->render('<x-badge>New</x-badge>'))
        ->toBe('<span class="inline-flex border text-blue-500 text-base">New</span>');
});

it('picks up values passed to the component tag', function () {
    expect($this->render('<x-badge variant="solid" size="sm">New</x-badge>'))
        ->toBe('<span class="inline-flex font-bold text-blue-500 text-sm">New</span>');
});

it('applies compound rules when every condition matches', function () {
    expect($this->render('<x-badge variant="solid" color="danger" />'))
        ->toContain('class="inline-flex font-bold text-red-500 text-base bg-red-500 text-white"');
});

it('accepts backed enums as axis values', function () {
    expect($this->render('<x-badge :color="$tone" />', ['tone' => Tone::Danger]))
        ->toContain('text-red-500');
});

it('appends outside classes last and keeps other attributes', function () {
    expect($this->render('<x-badge class="ml-2" id="x" data-foo="bar" />'))
        ->toBe('<span class="inline-flex border text-blue-500 text-base ml-2" id="x" data-foo="bar"></span>');
});

it('does not render undeclared axis props as html attributes', function () {
    $html = $this->render('<x-button variant="ghost">Go</x-button>');

    expect($html)->toBe('<button class="btn btn-ghost" type="button"> Go </button>');
});

it('lets attributes from the tag override merged defaults', function () {
    expect($this->render('<x-button type="submit" />'))->toContain('type="submit"')->not->toContain('type="button"');
});

it('styles named targets without leaking root attributes into them', function () {
    $html = $this->render('<x-button class="mt-2" id="b" :size="[\'icon\' => \'lg\']" icon>Go</x-button>');

    expect($html)->toBe('<button class="btn mt-2" id="b" type="button"> <svg class="w-6"></svg> Go </button>');
});

it('applies a preset passed as a prop and merges slot attributes', function () {
    $html = $this->render(<<<'BLADE'
        <x-card preset="danger">
            <x-slot:title class="text-xl">Hi</x-slot:title>
            Body
        </x-card>
    BLADE);

    expect($html)->toBe('<div class="shadow bg-red-50"> <h2 class="font-bold text-xl">Hi</h2> Body </div>');
});

it('lets explicit props win over a preset', function () {
    $html = $this->render('<x-card preset="danger" tone="blue"><x-slot:title>Hi</x-slot:title></x-card>');

    expect($html)->toContain('<div class="shadow bg-blue-50">');
});

it('maps booleans to "true" / "false" keys', function () {
    expect($this->render('<x-toggle />'))->toBe('<span class="bg-gray-200"></span>')
        ->and($this->render('<x-toggle checked />'))->toBe('<span class="bg-green-500"></span>');
});

it('still supports the explicit fluent api with native bag methods', function () {
    $html = $this->render('<x-fluent class="m-1" id="f" />');

    expect($html)->toBe('<div class="p-4 m-1"> <i class="text-red-500" aria-hidden="true"></i> </div>');
});

it('keeps recipes isolated between component instances', function () {
    $html = $this->render('<x-badge variant="solid" /><x-button />');

    expect($html)->toBe('<span class="inline-flex font-bold text-blue-500 text-base"></span> <button class="btn" type="button"> </button>');
});

it('runs the configured class merger', function () {
    Malevich\Malevich::mergeClassesUsing(fn (string $classes) => strtoupper($classes));

    expect($this->render('<x-toggle />'))->toBe('<span class="BG-GRAY-200"></span>');
});

it('supports string maps, compounds on plain props and directives from partials', function () {
    View::addLocation(__DIR__.'/../fixtures');

    expect($this->render('<x-doc />'))->toBe('<div class="p-2"><b class="font-semibold"></b></div>')
        ->and($this->render('<x-doc loading size="sm" />'))->toBe('<div class="p-1 cursor-wait gap-1"><b class="font-semibold"></b></div>');
});

it('only knows directives declared before @ui', function () {
    expect($this->render('<x-late size="md" />'))->toBe('<div size="md"></div>');
});

it('picks up a named slot with the same name as the target', function () {
    $html = $this->render(<<<'BLADE'
        <x-panel>
            <x-slot:title class="text-xl" id="t">Hi</x-slot:title>
            <x-slot:footer class="ignored">Bye</x-slot:footer>
        </x-panel>
    BLADE);

    expect($html)->toBe('<div> <h2 class="font-bold text-xl" id="t">Hi</h2> <p class="text-xs">Bye</p> </div>');
});

it('ignores a same-named prop that is not a slot', function () {
    expect($this->render('<x-panel title="Hi" footer="Bye" />'))
        ->toBe('<div> <h2 class="font-bold">Hi</h2> <p class="text-xs">Bye</p> </div>');
});
