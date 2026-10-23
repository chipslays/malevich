<?php

use Illuminate\View\ComponentAttributeBag;
use Malevich\Support\ScopedProps;

it('maps scoped attributes to explicitly declared camel-case props', function () {
    expect($this->render('<x-scoped-props icon:only icon:trailing="heart" loading:label="Saving" />'))
        ->toContain('<b>true</b>', '<i>heart</i>', '<label>Saving</label>', '<small>heart</small>')
        ->not->toContain('icon:only=')->not->toContain('icon:trailing=')->not->toContain('loading:label=')->not->toContain('icon-only=');
});

it('preserves bound values and empty labels', function () {
    expect($this->render('<x-scoped-props :icon:only="false" :loading:label="$label" />', ['label' => '']))
        ->toContain('<b>false</b>', '<label></label>');
});

it('retains all modifier types after Blade consumes the prop', function () {
    expect($this->render('<x-scoped-props icon:trailing.rounded.enabled[true].filled[false].limit[24].empty[]="heart" />'))
        ->toContain('<i>heart</i>', '<small>heart</small>', '&quot;rounded&quot;:true', '&quot;enabled&quot;:true', '&quot;filled&quot;:false', '&quot;limit&quot;:&quot;24&quot;', '&quot;empty&quot;:null')
        ->not->toContain('malevich-target-');
});

it('supports bound decorated props and escapes the value', function () {
    expect($this->render('<x-scoped-props :icon:trailing.filled[false]="$icon" />', ['icon' => '<script>alert(1)</script>']))
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;', '&quot;filled&quot;:false')
        ->not->toContain('<script>')->not->toContain('malevich-target-');
});

it('keeps ordinary camel-case and kebab-case input compatible', function () {
    expect($this->render('<x-scoped-props icon-only loading-label="Saving" />'))
        ->toContain('<b>true</b>', '<label>Saving</label>');

    expect($this->render('<x-scoped-props />'))
        ->toContain('<b>false</b>', '<label>Ready</label>');
});

it('gives scoped input precedence over its ordinary alias independent of attribute order', function () {
    foreach (['loading-label="Flat" loading:label="Scoped"', 'loading:label="Scoped" loading-label="Flat"'] as $attributes) {
        expect($this->render('<x-scoped-props '.$attributes.' />'))->toContain('<label>Scoped</label>');
    }
});

it('lets the last decorated scoped prop supply both its value and modifiers', function () {
    expect($this->render('<x-scoped-props icon:trailing.filled[true]="first" icon:trailing.filled[false]="second" />'))
        ->toContain('<i>second</i>', '<small>second</small>', '&quot;filled&quot;:false');
});

it('does not consume undeclared scoped input from any namespace', function () {
    expect($this->render('<x-scoped-props spinner:size="lg" wire:click="save" x-on:click="open = true" />'))
        ->toContain('spinner:size="lg"', 'wire:click="save"', 'x-on:click="open = true"');
});

it('uses declared props as the contract without reserving any namespace', function () {
    $bag = new ComponentAttributeBag(['wire:click' => 'save', 'x-on:click' => 'open', 'x-bind:class' => 'classes']);
    [$remaining, $aliases, $metadata] = ScopedProps::extract($bag, ['wireClick' => null, 'xOnClick' => null, 'xBindClass' => null]);

    expect($remaining->getAttributes())->toBe([]);
    expect($aliases)->toBe(['wireClick' => 'save', 'xOnClick' => 'open', 'xBindClass' => 'classes']);
    expect($metadata)->toBe($bag->getAttributes());
});

it('does not rewrite comments verbatim blocks or escaped props directives', function () {
    $template = <<<'BLADE'
        {{-- @props(['iconOnly' => false]) --}}
        @verbatim @props(['iconOnly' => false]) @endverbatim
        @@props(['iconOnly' => false])
        @php($note = "@props(['iconOnly' => false])")
        @php
            $note = "@props(['iconOnly' => false])";
        @endphp
        <?php $note = "@props(['iconOnly' => false])"; ?>
        BLADE;

    expect(ScopedProps::compile($template))->toBe($template);
});

it('recognizes whitespace before a props expression', function () {
    expect(ScopedProps::compile("@props (['iconOnly' => false])"))->toContain('ScopedProps::extract');
});

it('keeps metadata isolated between component instances', function () {
    $html = $this->render('<x-scoped-props icon:trailing.filled[false]="heart" /><x-scoped-props />');

    expect(substr_count($html, '&quot;filled&quot;:false'))->toBe(1);
    expect($html)->toContain('<em>[]</em>');
});

it('can prepare a template more than once without wrapping props again', function () {
    $compiled = ScopedProps::compile("@props(['iconOnly' => false])");

    expect(ScopedProps::compile($compiled))->toBe($compiled);
});

it('preserves target class choices when the same input is also a declared prop', function () {
    expect($this->render('<x-scoped-props icon:size="lg" />'))
        ->toContain('<u class="large-icon"></u>')
        ->not->toContain('icon:size=')->not->toContain('iconSize=');
});

it('turns "true" and "false" from the tag into booleans for props with a boolean default', function () {
    expect($this->render('<x-scoped-props icon-only="false" />'))->toContain('<b>false</b>');
    expect($this->render('<x-scoped-props icon-only="true" />'))->toContain('<b>true</b>');
    expect($this->render('<x-scoped-props icon:only="false" />'))->toContain('<b>false</b>');
});

it('leaves other strings and non-boolean props alone', function () {
    expect($this->render('<x-scoped-props icon-only="yes" loading-label="false" />'))
        ->toContain('<b>&quot;yes&quot;</b>', '<label>false</label>');
});

it('lists the props with a boolean default', function () {
    expect(ScopedProps::booleans(['loading' => false, 'open' => true, 'size' => 'md', 'icon' => null, 'title']))
        ->toBe(['loading', 'open']);
});
