<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

it('renders the complete inner-element documentation examples', function () {
    $files = new Filesystem;
    $directory = sys_get_temp_dir().'/malevich-examples-'.uniqid();
    $files->ensureDirectoryExists($directory);

    try {
        $guide = $files->get(dirname(__DIR__, 2).'/docs/05-inner-elements.md');
        preg_match_all('/```blade\s+\{\{-- ([\w-]+)\.blade\.php --\}\}(.*?)```/s', $guide, $examples, PREG_SET_ORDER);

        expect($examples)->toHaveCount(3);

        foreach ($examples as $example) {
            $files->put($directory.'/'.$example[1].'.blade.php', $example[2]);
        }

        Blade::anonymousComponentPath($directory, 'guide');

        expect($this->render('<x-guide::card size="sm"><x-slot:title size="lg" id="billing-title">Billing</x-slot:title> Payment due. </x-guide::card>'))
            ->toContain('p-3 text-sm', '<h2 class="font-semibold text-2xl" id="billing-title">Billing</h2>');

        expect($this->render('<x-guide::save-button size="sm" spinner:size="lg" loading>Saving</x-guide::save-button>'))
            ->toContain('h-8 px-3 text-sm', 'size-5', '<svg');

        expect($this->render('<x-guide::caption text:value.uppercase.limit[12]="Account settings" />'))
            ->toContain('ACCOUNT SET…')
            ->not->toContain('text:value')->not->toContain('malevich-target-');

        expect($this->render('<x-guide::caption text:value.uppercase[false].limit[]="Account settings" />'))
            ->toContain('Account settings');
    } finally {
        $files->deleteDirectory($directory);
    }
});
