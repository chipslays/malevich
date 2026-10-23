<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Malevich\Catalog\Catalog;
use Malevich\Malevich;

beforeEach(function () {
    Blade::anonymousComponentPath(dirname(__DIR__, 2).'/resources/kit', 'kit');
    Malevich::strict();
});

it('renders every combination of button variant and color', function () {
    foreach (['solid', 'soft', 'outline', 'ghost', 'link'] as $variant) {
        foreach (['primary' => 'primary', 'secondary' => 'secondary', 'accent' => 'accent', 'neutral' => 'gray', 'contrast' => 'gray', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger', 'info' => 'info'] as $color => $hue) {
            $html = $this->render('<x-kit::button :variant="$variant" :color="$color">Save</x-kit::button>', compact('variant', 'color'));

            expect($html)
                ->toContain('<button', 'type="button"', 'focus-visible:outline-')
                ->not->toContain(' variant=', ' color=', 'var(', '--button-', 'style=');

            $expected = match ($variant) {
                'solid' => 'bg-'.$hue.'-'.match ($color) {
                    'neutral' => '100',
                    'contrast' => '950',
                    'success', 'warning', 'danger', 'info' => '500',
                    default => '600',
                },
                'soft' => $color === 'contrast' ? 'bg-gray-950/10' : 'bg-'.$hue.'-'.(in_array($color, ['success', 'warning', 'danger', 'info'], true) ? '100' : '50'),
                'outline' => $color === 'contrast' ? 'border-gray-950' : 'border-'.$hue.'-300',
                'ghost', 'link' => $color === 'contrast' ? 'text-gray-950' : 'text-'.$hue.'-'.(in_array($color, ['neutral', 'success', 'warning'], true) ? '800' : '700'),
            };

            expect($html)->toHaveClasses($expected);

            if (in_array($color, ['success', 'warning', 'danger', 'info'], true)) {
                if ($variant === 'solid') {
                    expect($html)->toHaveClasses(['hover:bg-'.$hue.'-600', $color === 'warning' ? 'text-black' : 'text-white']);
                } elseif ($variant === 'soft') {
                    expect($html)->toHaveClasses(['hover:bg-'.$hue.'-200', 'dark:bg-'.$hue.'-950', 'dark:hover:bg-'.$hue.'-900']);
                }
            }
        }
    }
});

it('gives contrast its own theme-aware styles for every transparent treatment', function (string $variant, array $expected) {
    $html = $this->render('<x-kit::button color="contrast" :variant="$variant">Continue</x-kit::button>', compact('variant'));

    expect($html)->toContain('text-gray-950 dark:text-gray-50', 'focus-visible:outline-gray-950 dark:focus-visible:outline-gray-100', ...$expected)
        ->not->toContain('text-gray-800', 'dark:text-gray-200', 'border-gray-300');
})->with([
    'soft' => ['soft', ['bg-gray-950/10 hover:bg-gray-950/15', 'dark:bg-gray-100/15 dark:hover:bg-gray-100/20']],
    'outline' => ['outline', ['border-gray-950 dark:border-gray-100', 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15']],
    'ghost' => ['ghost', ['bg-transparent', 'hover:bg-gray-950/10 dark:hover:bg-gray-100/15']],
    'link' => ['link', ['bg-transparent', 'hover:underline']],
]);

it('provides theme-matching and contrasting monochrome solid buttons', function () {
    expect($this->render('<x-kit::button color="neutral">Cancel</x-kit::button>'))
        ->toContain('bg-gray-100 text-gray-950 hover:bg-gray-200', 'dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800');

    expect($this->render('<x-kit::button color="contrast">Continue</x-kit::button>'))
        ->toContain('bg-gray-950 text-white hover:bg-gray-800', 'dark:bg-gray-100 dark:text-gray-950 dark:hover:bg-gray-300');
});

it('renders all sizes and radii and makes icon buttons square without horizontal padding', function () {
    foreach (['xs' => 'h-7', 'sm' => 'h-8', 'md' => 'h-10', 'lg' => 'h-11', 'xl' => 'h-12'] as $size => $height) {
        expect($this->render('<x-kit::button :size="$size" aria-label="Add"><x-slot:icon><svg></svg></x-slot:icon></x-kit::button>', compact('size')))
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
        ->toContain('<span ')->not->toContain('<button', 'type=');
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
        ->not->toContain('aria-busy=', 'disabled=', 'animate-spin', 'opacity-0', '<svg');
});

it('styles named slots and passes user attributes through to the right elements', function () {
    $html = $this->render('<x-kit::button class="w-full" id="save"><x-slot:icon class="text-current" data-part="leading"><svg></svg></x-slot:icon> Save <x-slot:trailing data-part="trailing"><svg></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('w-full', 'id="save"', 'text-current', 'data-part="leading"', 'data-part="trailing"', 'aria-hidden="true"');
    expect(substr_count($html, 'id="save"'))->toBe(1);
});

it('uses literal Tailwind palettes for the requested treatment and color', function () {
    expect($this->render('<x-kit::button variant="solid" color="danger">Delete</x-kit::button>'))
        ->toHaveClasses(['bg-danger-500', 'text-white', 'hover:bg-danger-600'])
        ->not->toContain('text-danger-700', 'border-danger-300');

    expect($this->render('<x-kit::button variant="outline" color="danger">Delete</x-kit::button>'))
        ->toContain('border-danger-300', 'text-danger-700', 'dark:text-danger-300', 'hover:bg-danger-50')
        ->not->toContain('bg-danger-500', 'text-white');
});

it('keeps original content in layout and absolutely overlays only a spinner for explicitly empty loading labels', function (string $loadingLabel) {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel>{{ $loadingLabel }}</x-slot:loadingLabel> Save changes </x-kit::button>', compact('loadingLabel'));

    expect($html)
        ->toMatch('/<span class="[^"]*opacity-0[^"]*"> Save changes <\/span>/')
        ->toContain('relative inline-flex', 'h-10', 'px-4', 'absolute inset-0', 'aria-busy="true"', 'animate-spin')
        ->not->toContain('hidden', 'invisible', 'loading:label=', 'truncate');
})->with(['empty' => '', 'whitespace' => '   ']);

it('shows a replacement label only while loading without removing the original sizing content', function () {
    $html = $this->render('<x-kit::button loading><x-slot:loadingLabel>Saving changes</x-slot:loadingLabel> Save </x-kit::button>');

    expect($html)
        ->toMatch('/<span class="[^"]*opacity-0[^"]*" aria-hidden="true"> Save <\/span>/')
        ->toContain('absolute inset-0', 'animate-spin', '<span class="truncate">Saving changes</span>')
        ->not->toContain('loading:label=');

    expect($this->render('<x-kit::button><x-slot:loadingLabel>Saving changes</x-slot:loadingLabel> Save </x-kit::button>'))
        ->toContain('Save')
        ->not->toContain('Saving changes', 'animate-spin', 'absolute inset-0');
});

it('keeps icon-only loading buttons square and hides their loading text', function () {
    expect($this->render('<x-kit::button loading aria-label="Save"><x-slot:loadingLabel>Saving</x-slot:loadingLabel><x-slot:icon><svg></svg></x-slot:icon></x-kit::button>'))
        ->toContain('aspect-square', 'p-0', 'aria-label="Save"', 'absolute inset-0', 'animate-spin')
        ->not->toContain('Saving', 'truncate');
});

it('sizes leading and trailing icon slots independently and accepts targeted spinner size', function () {
    $html = $this->render('<x-kit::button size="sm" spinner:size="xs" loading><x-slot:loadingLabel></x-slot:loadingLabel><x-slot:icon size="xl"><svg></svg></x-slot:icon> Save <x-slot:trailing size="lg"><svg></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('h-8', 'text-sm', '[&>svg]:size-5.5', '[&>svg]:size-5')
        ->toMatch('/<svg class="[^"]*size-3\.5[^"]*"/')
        ->not->toContain('spinner:size=', 'size="xl"', 'size="lg"');
});

it('keeps the default label and trailing icon while replacing only the leading icon with a spinner', function () {
    $html = $this->render('<x-kit::button loading><x-slot:icon><svg data-icon="leading"></svg></x-slot:icon> Save changes <x-slot:trailing><svg data-icon="trailing"></svg></x-slot:trailing></x-kit::button>');

    expect($html)->toContain('Save changes', 'animate-spin', 'data-icon="trailing"')
        ->not->toContain('data-icon="leading"', 'opacity-0', 'absolute inset-0');
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
        ->not->toContain('truncate', 'no replacement label');
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
    expect($this->render('<x-kit::button variant="ghost" inset aria-label="Add"><x-slot:icon><svg></svg></x-slot:icon></x-kit::button>'))
        ->toContain('aspect-square')->not->toContain('-mx-');
});

it('is discoverable in the catalog with a working example', function () {
    $catalog = Catalog::make();
    $component = $catalog->components()['kit::button'];

    expect($component['options']['variant'])->toBe(['solid', 'soft', 'outline', 'ghost', 'link']);
    expect($component['options']['color'])->toBe(['primary', 'secondary', 'accent', 'neutral', 'contrast', 'success', 'warning', 'danger', 'info']);
    expect($component['options']['size'])->toBe(['xs', 'sm', 'md', 'lg', 'xl']);
    expect($component['options'])->not->toHaveKey('iconOnly');
    expect($catalog->example($component['file']))->not->toBeNull();
    expect($catalog->sheet('kit::button', $component)['default']->error)->toBeNull();
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
