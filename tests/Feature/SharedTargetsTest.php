<?php

use Illuminate\Filesystem\Filesystem;
use Malevich\Recipe;
use Malevich\Support\ComponentScanner;

it('styles every element of a comma-separated target key alike', function () {
    expect($this->render('<x-shared-targets />'))
        ->toBe('<div class="root-sm"><i class="shared-sm both-compound"></i><u class="shared-sm only-b both-compound"></u></div>');

    expect($this->render('<x-shared-targets size="lg" />'))
        ->toBe('<div class="root-lg"><i class="shared-lg"></i><u class="shared-lg"></u></div>');
});

it('lets each element of a shared key still take its own option value', function () {
    expect($this->render('<x-shared-targets b:size="lg" />'))
        ->toContain('<i class="shared-sm both-compound">', '<u class="shared-lg">');
});

it('rejects an empty element name in a target key', function () {
    expect(fn () => (new Recipe('default'))->cases('size', ['sm' => ['a,' => 'x']]))
        ->toThrow(InvalidArgumentException::class, 'Empty element name');
});

it('reads shared target keys for lint and the playground', function () {
    $component = (new ComponentScanner(new Filesystem, app('view')->getFinder()))->component(__DIR__.'/../fixtures/components/shared-targets.blade.php');

    expect($component['targets'])->toContain('a', 'b')->not->toContain('a,b');
});
