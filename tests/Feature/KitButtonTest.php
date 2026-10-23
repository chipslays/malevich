<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Malevich\Playground\Playground;
use Malevich\Malevich;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
    Malevich::strict();
});

it('renders every combination of button variant and color', function () {
    // [solid classes, soft classes, text color of the transparent treatments]
    $palette = [
        'primary' => [['bg-primary-600', 'text-white', 'hover:bg-primary-700'], ['bg-primary-100', 'dark:bg-primary-400/15'], 'text-primary-700'],
        'secondary' => [['bg-secondary-600', 'text-white', 'hover:bg-secondary-700'], ['bg-secondary-100', 'dark:bg-secondary-400/15'], 'text-secondary-700'],
        'accent' => [['bg-accent-600', 'text-white', 'hover:bg-accent-700'], ['bg-accent-100', 'dark:bg-accent-400/15'], 'text-accent-700'],
        'neutral' => [['bg-gray-100', 'text-gray-950', 'dark:bg-gray-800'], ['bg-gray-950/5', 'dark:bg-gray-400/15'], 'text-gray-800'],
        'contrast' => [['bg-gray-950', 'text-white', 'dark:bg-gray-100'], ['bg-gray-950/10', 'dark:bg-gray-100/15'], 'text-gray-950'],
        'success' => [['bg-success-700', 'text-white', 'hover:bg-success-800'], ['bg-success-100', 'dark:bg-success-400/15'], 'text-success-800'],
        'warning' => [['bg-warning-400', 'text-warning-950', 'hover:bg-warning-500'], ['bg-warning-100', 'dark:bg-warning-400/15'], 'text-warning-800'],
        'danger' => [['bg-danger-600', 'text-white', 'hover:bg-danger-700'], ['bg-danger-100', 'dark:bg-danger-400/15'], 'text-danger-800'],
        'info' => [['bg-info-700', 'text-white', 'hover:bg-info-800'], ['bg-info-100', 'dark:bg-info-400/15'], 'text-info-800'],
    ];

    foreach (['solid', 'soft', 'outline', 'ghost', 'link'] as $variant) {
        foreach ($palette as $color => [$solid, $soft, $foreground]) {
            $html = $this->render('<x-kit::button :variant="$variant" :color="$color">Save</x-kit::button>', compact('variant', 'color'));

            expect($html)
                ->toContain('<button', 'type="button"', 'focus-visible:outline-')
                ->not->toContain(' variant=')->not->toContain(' color=')->not->toContain('var(')->not->toContain('--button-')->not->toContain('style=');

            expect($html)->toHaveClasses(match ($variant) {
                'solid' => $solid,
                'soft' => [...$soft, $foreground],
                default => $foreground,
            });

            if ($variant !== 'solid') {
                expect($html)->not->toContain($solid[0].' ');
            }
        }
    }
});

it('tints surface buttons with a border of their role', function (string $color, array $expected) {
    expect($this->render('<x-kit::button variant="surface" :color="$color">Save</x-kit::button>', compact('color')))
        ->toHaveClasses($expected)
        ->not->toContain('shadow-xs');
})->with([
    'primary' => ['primary', ['bg-primary-50', 'border-primary-200', 'hover:bg-primary-100', 'dark:bg-primary-400/10', 'dark:border-primary-400/25', 'text-primary-700']],
    'neutral' => ['neutral', ['bg-gray-50', 'border-gray-200', 'dark:bg-gray-400/10', 'text-gray-800']],
    'contrast' => ['contrast', ['bg-gray-950/5', 'border-gray-950/30', 'dark:bg-gray-100/10', 'text-gray-950']],
    'danger' => ['danger', ['bg-danger-50', 'border-danger-200', 'dark:border-danger-400/25', 'text-danger-800']],
]);

it('raises elevated buttons on a neutral background with the text of their role', function (string $color, string $foreground) {
    expect($this->render('<x-kit::button variant="elevated" :color="$color">Save</x-kit::button>', compact('color')))
        ->toHaveClasses(['bg-white', 'border-gray-200', 'shadow-xs', 'hover:bg-gray-50', 'dark:bg-white/5', 'dark:border-white/10', $foreground])
        ->not->toContain('border-transparent')
        ->not->toContain('-50 hover:bg-'.$color);
})->with([
    'primary' => ['primary', 'text-primary-700'],
    'neutral' => ['neutral', 'text-gray-800'],
    'success' => ['success', 'text-success-800'],
]);

it('gives contrast its own theme-aware styles for every transparent treatment', function (string $variant, array $expected) {
    $html = $this->render('<x-kit::button color="contrast" :variant="$variant">Continue</x-kit::button>', compact('variant'));

    expect($html)->toContain('text-gray-950 dark:text-gray-50', 'focus-visible:outline-gray-950 dark:focus-visible:outline-gray-100', ...$expected)
        ->not->toContain('text-gray-800')->not->toContain('dark:text-gray-200')->not->toContain('border-gray-300');
})->with([
    'soft' => ['soft', ['bg-gray-950/10 hover:bg-gray-950/15', 'dark:bg-gray-100/15 dark:hover:bg-gray-100/20']],
    'outline' => ['outline', ['border-gray-950 dark:border-gray-100', 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15']],
    'ghost' => ['ghost', ['bg-transparent', 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15']],
    'link' => ['link', ['bg-transparent', 'hover:underline']],
]);

it('provides theme-matching and contrasting monochrome solid buttons', function () {
    expect($this->render('<x-kit::button color="neutral">Cancel</x-kit::button>'))
        ->toHaveClasses(['bg-gray-100', 'text-gray-950', 'hover:bg-gray-200', 'dark:bg-gray-800', 'dark:text-gray-100', 'dark:hover:bg-gray-700']);

    expect($this->render('<x-kit::button color="contrast">Continue</x-kit::button>'))
        ->toHaveClasses(['bg-gray-950', 'text-white', 'hover:bg-gray-800', 'dark:bg-gray-100', 'dark:text-gray-950', 'dark:hover:bg-gray-300']);
});

it('renders all sizes and radii and makes icon buttons square without horizontal padding', function () {
    foreach (['xs' => 'h-7', 'sm' => 'h-8', 'md' => 'h-10', 'lg' => 'h-11', 'xl' => 'h-12'] as $size => $height) {
        expect($this->render('<x-kit::button :size="$size" aria-label="Add"><x-slot:leading><svg></svg></x-slot:leading></x-kit::button>', compact('size')))
            ->toContain($height, 'aspect-square', 'p-0', 'aria-label="Add"')
            ->not->toContain('icon:only=')
            ->not->toMatch('/\bpx-\d+/');
    }

    foreach (['brand', 'none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', 'full'] as $radius) {
        expect($this->render('<x-kit::button :radius="$radius">Save</x-kit::button>', compact('radius')))
            ->toContain('rounded-'.$radius);
    }

    expect($this->render('<x-kit::button>Save</x-kit::button>'))->toContain('rounded-brand');
    expect($this->render('<x-kit::button radius="squircle">Save</x-kit::button>'))
        ->toContain('rounded-2xl', '[corner-shape:squircle]');
});

it('preserves native button and link behavior', function () {
    expect($this->render('<x-kit::button type="submit" name="action" value="save" wire:click="save">Save</x-kit::button>'))
        ->toContain('type="submit"', 'name="action"', 'value="save"', 'wire:click="save"');

    expect($this->render('<x-kit::button href="/docs" target="_blank">Docs</x-kit::button>'))
        ->toContain('<a ', 'href="/docs"', 'rel="noopener noreferrer"');

    expect($this->render('<x-kit::button href="/docs" disabled>Docs</x-kit::button>'))
        ->toContain('<a ', 'aria-disabled="true"', 'tabindex="-1"', 'data-disabled')
        ->not->toContain('href=');

    expect($this->render('<x-kit::button as="span">Label</x-kit::button>'))
        ->toContain('<span ')->not->toContain('<button')->not->toContain('type=');
});

it('forwards Livewire and Alpine namespaces unchanged through the primitive', function () {
    $html = $this->render('<x-kit::button wire:click="save" wire:model.live="action" wire:loading.attr="disabled" wire:target="save" x-on:click="open = true" x-bind:class="classes" ::class="active ? \'active\' : \'\'">Save</x-kit::button>');

    expect($html)->toContain('wire:click="save"', 'wire:model.live="action"', 'wire:loading.attr="disabled"', 'wire:target="save"', 'x-on:click="open = true"', 'x-bind:class="classes"', ':class=');
    expect(substr_count($html, 'wire:click="save"'))->toBe(1);
});

it('disables loading buttons and links even when disabled is explicitly false', function () {
    expect($this->render('<x-kit::button loading :disabled="false" aria-busy="false">Saving</x-kit::button>'))
        ->toContain('disabled="disabled"', 'aria-busy="true"', 'animate-spin', 'Saving')
        ->not->toContain('opacity-0');

    expect($this->render('<x-kit::button href="/docs" loading>Docs</x-kit::button>'))
        ->toContain('<a ', 'aria-disabled="true"', 'tabindex="-1"')
        ->not->toContain('href=');

    expect($this->render('<x-kit::button :loading="false" :disabled="false">Save</x-kit::button>'))
        ->not->toContain('aria-busy=')->not->toContain('disabled=')->not->toContain('animate-spin')->not->toContain('opacity-0')->not->toContain('<svg');
});

it('styles named slots and passes user attributes through to the right elements', function () {
    $html = $this->render('<x-kit::button class="w-full" id="save"><x-slot:leading class="text-current" data-part="leading"><svg></svg></x-slot:leading> Save <x-slot:trailing data-part="trailing"><svg></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('w-full', 'id="save"', 'text-current', 'data-part="leading"', 'data-part="trailing"', 'aria-hidden="true"');
    expect(substr_count($html, 'id="save"'))->toBe(1);
});

it('uses literal Tailwind palettes for the requested treatment and color', function () {
    expect($this->render('<x-kit::button variant="solid" color="danger">Delete</x-kit::button>'))
        ->toHaveClasses(['bg-danger-600', 'text-white', 'hover:bg-danger-700'])
        ->not->toContain('text-danger-800')->not->toContain('border-danger-300');

    expect($this->render('<x-kit::button variant="outline" color="danger">Delete</x-kit::button>'))
        ->toContain('border-danger-300', 'text-danger-800', 'dark:text-danger-300', 'hover:bg-danger-50')
        ->not->toContain('bg-danger-600')->not->toContain('text-white');
});

it('keeps original content in layout and absolutely overlays only a spinner for explicitly empty loading labels', function (string $loadingLabel) {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel>{{ $loadingLabel }}</x-slot:loadingLabel> Save changes </x-kit::button>', compact('loadingLabel'));

    expect($html)
        ->toMatch('/<span class="[^"]*opacity-0[^"]*"> Save changes <\/span>/')
        ->toContain('relative inline-flex', 'h-10', 'px-4', 'absolute inset-0', 'aria-busy="true"', 'animate-spin')
        ->not->toMatch('/class="[^"]*(?<![\w-])(hidden|invisible)(?![\w-])/')
        ->not->toContain('loading:label=')->not->toContain('truncate');
})->with(['empty' => '', 'whitespace' => '   ']);

it('shows a replacement label only while loading without removing the original sizing content', function () {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel>Saving changes</x-slot:loadingLabel> Save </x-kit::button>');

    expect($html)
        ->toMatch('/<span class="[^"]*opacity-0[^"]*" aria-hidden="true"> Save <\/span>/')
        ->toContain('absolute inset-0', 'animate-spin', '<span class="truncate">Saving changes</span>')
        ->not->toContain('loading:label=');

    expect($this->render('<x-kit::button><x-slot:loadingLabel>Saving changes</x-slot:loadingLabel> Save </x-kit::button>'))
        ->toContain('Save')
        ->not->toContain('Saving changes')->not->toContain('animate-spin')->not->toContain('absolute inset-0');
});

it('keeps icon-only loading buttons square and hides their loading text', function () {
    expect($this->render('<x-kit::button loading aria-label="Save"><x-slot:loadingLabel>Saving</x-slot:loadingLabel><x-slot:leading><svg></svg></x-slot:leading></x-kit::button>'))
        ->toContain('aspect-square', 'p-0', 'aria-label="Save"', 'absolute inset-0', 'animate-spin')
        ->not->toContain('Saving')->not->toContain('truncate');
});

it('sizes leading and trailing icon slots independently and accepts targeted spinner size', function () {
    $html = $this->render('<x-kit::button size="sm" spinner:size="xs" loading><x-slot:loadingLabel></x-slot:loadingLabel><x-slot:leading size="xl"><svg></svg></x-slot:leading> Save <x-slot:trailing size="lg"><svg></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('h-8', 'text-sm', '[&>svg]:size-5.5', '[&>svg]:size-5')
        ->toMatch('/<svg class="[^"]*size-3\.5[^"]*"/')
        ->not->toContain('spinner:size=')->not->toContain('size="xl"')->not->toContain('size="lg"');
});

it('keeps the default label and trailing icon while replacing only the leading icon with a spinner', function () {
    $html = $this->render('<x-kit::button loading><x-slot:leading><svg data-icon="leading"></svg></x-slot:leading> Save changes <x-slot:trailing><svg data-icon="trailing"></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('Save changes', 'animate-spin', 'data-icon="trailing"')
        ->not->toContain('data-icon="leading"')->not->toContain('opacity-0')->not->toContain('absolute inset-0');
    expect(strpos($html, 'animate-spin'))->toBeLessThan(strpos($html, 'Save changes'));
});

it('preserves the original label when the loading slot is omitted', function () {
    expect($this->render('<x-kit::button loading>Save</x-kit::button>'))
        ->toContain('Save', 'animate-spin')->not->toContain('opacity-0');
});

it('renders rich loading slot content and forwards slot attributes to its wrapper', function () {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel class="font-semibold" data-label="busy"><strong>Saving</strong> <x-malevich::primitive as="em">changes</x-malevich::primitive></x-slot:loadingLabel> Save changes </x-kit::button>');

    expect($html)->toContain('opacity-0', 'aria-hidden="true"', 'truncate font-semibold', 'data-label="busy"', '<strong>Saving</strong>', '<em>changes</em>')
        ->not->toContain('&lt;strong&gt;');
});

it('treats comments in a loading slot as an explicit empty override', function () {
    expect($this->render('<x-kit::button loading><x-slot:loadingLabel><!-- no replacement label --></x-slot:loadingLabel> Save </x-kit::button>'))
        ->toContain('opacity-0', 'animate-spin')
        ->not->toContain('truncate')->not->toContain('no replacement label');
});

it('compensates transparent button padding for every size and leaves the hit area padded', function () {
    foreach (['xs' => 2, 'sm' => 3, 'md' => 4, 'lg' => 5, 'xl' => 6] as $size => $spacing) {
        foreach (['ghost', 'link'] as $variant) {
            expect($this->render('<x-kit::button :size="$size" :variant="$variant" inset>Action</x-kit::button>', compact('size', 'variant')))
                ->toContain('-mx-'.$spacing, 'px-'.$spacing)
                ->not->toContain('inset=');
        }
    }
});

it('does not inset visible treatments ordinary buttons or icon-only buttons', function () {
    foreach (['solid', 'soft', 'outline'] as $variant) {
        expect($this->render('<x-kit::button :variant="$variant" inset>Action</x-kit::button>', compact('variant')))
            ->not->toContain('-mx-');
    }

    expect($this->render('<x-kit::button variant="ghost">Action</x-kit::button>'))->not->toContain('-mx-');
    expect($this->render('<x-kit::button variant="ghost" :inset="false">Action</x-kit::button>'))->not->toContain('-mx-');
    expect($this->render('<x-kit::button variant="ghost" inset aria-label="Add"><x-slot:leading><svg></svg></x-slot:leading></x-kit::button>'))
        ->toContain('aspect-square')->not->toContain('-mx-');
});

it('is discoverable in the playground with a working example', function () {
    $playground = Playground::make();
    $component = $playground->components()['kit::button'];

    expect($component['options']['variant'])->toBe(['solid', 'soft', 'surface', 'elevated', 'outline', 'ghost', 'link']);
    expect($component['options']['color'])->toBe(['primary', 'secondary', 'accent', 'neutral', 'contrast', 'success', 'warning', 'danger', 'info']);
    expect($component['options']['size'])->toBe(['xs', 'sm', 'md', 'lg', 'xl']);
    expect($component['options'])->not->toHaveKey('iconOnly');
    expect($playground->example($component['file']))->not->toBeNull();
    expect($playground->sheet('kit::button', $component)['default']->error)->toBeNull();
});

it('detects icon buttons from missing text even with whitespace comments or a default-slot SVG', function (string $content) {
    expect($this->render('<x-kit::button aria-label="Add">'.$content.'</x-kit::button>'))
        ->toContain('aspect-square', 'p-0')
        ->not->toMatch('/\bpx-\d+/');
})->with(['empty' => '', 'whitespace' => '   ', 'comment' => '<!-- no label -->', 'svg' => '<svg viewBox="0 0 24 24"><path d="M0 0" /></svg>']);

it('keeps ordinary padding when the label contains text wrapped in markup', function () {
    expect($this->render('<x-kit::button><strong>Save</strong></x-kit::button>'))
        ->toContain('px-4', '<strong>Save</strong>')
        ->not->toContain('aspect-square');
});

it('rejects removed palette color names', function () {
    expect(fn () => $this->render('<x-kit::button color="violet">Save</x-kit::button>'))
        ->toThrow(ViewException::class, 'violet');
});

it('treats loading="false" and disabled="false" from the tag as off', function () {
    expect($this->render('<x-kit::button loading="false" disabled="false">Save</x-kit::button>'))
        ->toContain('Save')
        ->not->toContain('animate-spin')
        ->not->toContain('aria-busy')
        ->not->toContain('disabled="')
        ->not->toContain(' pointer-events-none"');
});

it('lets square override the detection of icon buttons', function () {
    expect($this->render('<x-kit::button square aria-label="Add"><span class="sr-only">Add</span><svg></svg></x-kit::button>'))
        ->toHaveClasses(['aspect-square', 'p-0'])
        ->not->toContain('px-4');

    expect($this->render('<x-kit::button :square="false" aria-label="Add"><svg></svg></x-kit::button>'))
        ->toHaveClasses('px-4')
        ->not->toContain('aspect-square');
});

it('stretches a block button to the full width', function () {
    expect($this->render('<x-kit::button block>Continue</x-kit::button>'))->toHaveClasses('w-full');
    expect($this->render('<x-kit::button>Continue</x-kit::button>'))->not->toContain('w-full');
    expect($this->render('<x-kit::button block="false">Continue</x-kit::button>'))->not->toContain('w-full');
});

it('declares its palette with conditional cases instead of building a recipe by hand', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/kit/button.blade.php');

    expect($source)->toContain("when: ['variant' => 'solid']")
        ->not->toContain('Malevich::recipe(')
        ->not->toContain('filter_var($loading');
});
