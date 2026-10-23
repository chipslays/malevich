<?php

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ViewException;
use Malevich\Element;
use Malevich\Malevich;
use Malevich\Support\TargetAttribute;

it('restores transport keys without rewriting key-looking text in values', function () {
    $key = 'malevich-target-'.bin2hex('custom:event.enabled[false]');
    $value = "'{$key}' => example";
    $compiled = '<?php $attributes = ['.var_export($key, true).' => '.var_export($value, true).']; ?>';

    expect(TargetAttribute::restore($compiled))->toBe('<?php $attributes = [\'custom:event.enabled[false]\' => '.var_export($value, true).']; ?>');
});

it('passes arbitrary unowned namespaces and their modifiers through unchanged', function (string $component) {
    $html = $this->render('<x-'.$component.' wire:loading.attr="disabled" x:custom.flag[false]="x" x-on:click.once="open" analytics:track.once.rate[24].empty[]="save" :custom:event.enabled[false]="$value" />', ['value' => '<saved>']);

    expect($html)->toContain('wire:loading.attr="disabled"', 'x:custom.flag[false]="x"', 'x-on:click.once="open"', 'analytics:track.once.rate[24].empty[]="save"', 'custom:event.enabled[false]="&lt;saved&gt;"')
        ->not->toContain('malevich-target-')->not->toContain('malevichTarget');
})->with(['target-props', 'scoped-props', 'passthrough']);

it('allows any explicitly declared target namespace', function (string $target) {
    $bag = new ComponentAttributeBag([$target.':size' => 'lg', 'unrelated:size' => 'external']);
    Malevich::recipe($bag)->option('size', $target, ['lg' => 'large']);

    expect((new Element($bag, $target))->toClasses())->toBe('large');
    expect((new Element($bag))->toHtml())->toContain('unrelated:size="external"')->not->toContain($target.':size');
})->with(['wire', 'x', 'x-on', 'analytics']);

it('overrides a global option only on its target including compound conditions', function () {
    expect($this->render('<x-target-props size="xs" icon:size="xl" />'))
        ->toContain('<button class="root-xs">', '<i class="icon-xl large-icon">')
        ->not->toContain('icon:size=')->not->toContain('size="xs"');
});

it('supports bound values and target attributes without a class map', function () {
    expect($this->render('<x-target-props :icon:size="$size" icon:trailing="heart" icon:aria-label="Favorite" />', ['size' => 'xl']))
        ->toContain('icon-xl large-icon', 'trailing="heart"', 'aria-label="Favorite"', '<b>heart</b>')
        ->not->toContain('icon:trailing=')->not->toContain('icon:aria-label=');
});

it('parses boolean and valued modifiers without including them in HTML names', function () {
    expect($this->render('<x-target-props icon:trailing.mod1.mod2[value]="heart" />'))
        ->toContain('<b>heart</b>', '&quot;mod1&quot;:true', '&quot;mod2&quot;:&quot;value&quot;')
        ->not->toContain('mod1=')->not->toContain('malevich-target-');
});

it('converts only lowercase boolean modifier literals and preserves other strings', function (string $argument, string $expected) {
    expect($this->render('<x-target-props icon:trailing.mod1.mod2['.$argument.']="heart" />'))
        ->toContain('<b>heart</b>', '&quot;mod1&quot;:true', '&quot;mod2&quot;:'.$expected)
        ->not->toContain('malevich-target-');
})->with([
    'true' => ['true', 'true'],
    'false' => ['false', 'false'],
    'text' => ['value', '&quot;value&quot;'],
    'number' => ['24', '&quot;24&quot;'],
    'empty' => ['', 'null'],
    'case sensitive' => ['True', '&quot;True&quot;'],
]);

it('preserves false in bound and repeated modifiers', function () {
    expect($this->render('<x-target-props :icon:trailing.mod2[true].mod2[false]="$icon" />', ['icon' => 'heart']))
        ->toContain('<b>heart</b>', '&quot;mod2&quot;:false');
});

it('keeps bound modifier values as data and escapes them', function () {
    expect($this->render('<x-target-props :icon:trailing.mod1.mod2[value]="$icon" />', ['icon' => '<script>alert(1)</script>']))
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', '&quot;mod2&quot;:&quot;value&quot;')
        ->not->toContain('<script>');
});

it('gives explicit API choices precedence over targeted values and preserves array syntax', function () {
    $bag = new ComponentAttributeBag(['size' => 'xs', 'icon:size' => 'xl']);
    Malevich::recipe($bag)->option('size', 'icon', ['xs' => 'small', 'xl' => 'large']);
    $icon = new Element($bag, 'icon', ['size' => ['icon' => 'xs']]);

    expect($icon->toClasses())->toBe('large')
        ->and($icon->size('xs')->toClasses())->toBe('small');
});

it('preserves unrelated namespaces and quoted attribute contents', function () {
    expect($this->render('<x-target-props icon:size="xl" wire:click="save" x-on:click="open = true" title="icon:trailing.mod[value]" />'))
        ->toContain('wire:click="save"', 'x-on:click="open = true"', 'title="icon:trailing.mod[value]"');
});

it('respects strict mode and identifies the targeted option in debug output', function () {
    Malevich::debug();
    expect($this->render('<x-target-props icon:size="xl" />'))->toContain('from icon:size');

    Malevich::strict();
    expect(fn () => $this->render('<x-target-props icon:size="typo" />'))->toThrow(ViewException::class, 'typo');
});

it('uses targeted classes and attributes with named slots and preserves their precedence', function () {
    expect($this->render('<x-target-props icon:class="target-class" icon:title="target title"><x-slot:icon class="slot-class" title="slot title">Icon</x-slot:icon></x-target-props>'))
        ->toContain('slot title')
        ->not->toContain('title="target title"');
});

it('keeps generated root data attributes global', function () {
    config(['malevich.data_attributes' => true]);
    expect($this->render('<x-target-props size="xs" icon:size="xl" />'))
        ->toContain('data-size="xs"', 'icon-xl large-icon')
        ->not->toContain('data-size="xl"');
});

it('uses slot options before targeted and global options including compounds', function () {
    expect($this->render('<x-target-props size="xs" icon:size="xs"><x-slot:icon size="xl">Icon</x-slot:icon></x-target-props>'))
        ->toContain('<button class="root-xs">', '<i class="icon-xl large-icon">')
        ->not->toContain('size="xl"')->not->toContain('icon:size=');
});

it('supports bound slot options and reports their source', function () {
    Malevich::debug();
    expect($this->render('<x-target-props><x-slot:icon :size="$size">Icon</x-slot:icon></x-target-props>', ['size' => 'xl']))
        ->toContain('icon-xl large-icon', 'from slot');
});

it('validates slot option values in strict mode', function () {
    Malevich::strict();
    expect(fn () => $this->render('<x-target-props><x-slot:icon size="typo">Icon</x-slot:icon></x-target-props>'))
        ->toThrow(ViewException::class, 'typo');
});

it('keeps explicit choices first and treats false slot values as actual choices', function () {
    $bag = new ComponentAttributeBag(['icon:active' => true]);
    Malevich::recipe($bag)->option('active', 'icon', ['true' => 'on', 'false' => 'off']);
    $icon = (new Element($bag, 'icon', ['active' => true]))->slot(new ComponentAttributeBag(['active' => false]));

    expect($icon->toClasses())->toBe('off')
        ->and($icon->active(true)->toClasses())->toBe('on');
});

it('recognizes kebab-case slot options and falls back when the slot value is null', function () {
    $bag = new ComponentAttributeBag(['icon:iconOnly' => true]);
    Malevich::recipe($bag)->option('iconOnly', 'icon', ['true' => 'square', 'false' => 'wide']);
    $icon = new Element($bag, 'icon');

    expect($icon->slot(new ComponentAttributeBag(['icon-only' => false]))->toClasses())->toBe('wide')
        ->and($icon->slot(new ComponentAttributeBag(['icon-only' => null]))->toClasses())->toBe('square');
});

it('supports targeted false values without falling back to true', function () {
    $bag = new ComponentAttributeBag(['icon:active' => false]);
    Malevich::recipe($bag)->option('active', 'icon', ['true' => 'on', 'false' => 'off']);

    expect((new Element($bag, 'icon', ['active' => true]))->toClasses())->toBe('off');
});

it('accepts modifiers without brackets and valueless targeted flags', function () {
    expect($this->render('<x-target-props icon:trailing.rounded icon:class="custom-icon" />'))
        ->toContain('custom-icon', '&quot;rounded&quot;:true', 'trailing="trailing"');
});
