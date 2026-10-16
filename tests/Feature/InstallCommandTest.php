<?php

use Illuminate\Filesystem\Filesystem;
use Malevich\Console\Commands\InstallCommand;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->path = sys_get_temp_dir().'/malevich-'.uniqid();
    $this->config = config_path('malevich.php');
    $this->vscode = base_path('.vscode');

    config(['malevich.components.path' => $this->path]);

    // The testbench app is shared by every test: never leave files behind in it.
    expect($this->config)->not->toBeFile()
        ->and($this->vscode)->not->toBeDirectory();
});

afterEach(function () {
    $this->files->deleteDirectory($this->path);
    $this->files->deleteDirectory($this->vscode);
    $this->files->delete($this->config);
});

function vscodeSettings(): array
{
    return json_decode(file_get_contents(base_path('.vscode/settings.json')), true);
}

it('publishes the config, configures VS Code and adds the chosen components', function () {
    $this->artisan('malevich:install', ['--components' => ['button', 'badge']])->assertSuccessful();

    expect($this->config)->toBeFile()
        ->and($this->path.'/button.blade.php')->toBeFile()
        ->and($this->path.'/badge.blade.php')->toBeFile()
        ->and($this->path.'/card.blade.php')->not->toBeFile()
        ->and(vscodeSettings())->toBe([
            'tailwindCSS.includeLanguages' => ['blade' => 'html'],
            'tailwindCSS.experimental.classRegex' => [InstallCommand::classRegex()],
        ]);
});

it('builds the IntelliSense pattern from the configured options', function () {
    config(['malevich.options' => ['variant', 'radius'], 'malevich.cases_directive' => 'palette']);
    Malevich\Malevich::flushConfig();

    expect(InstallCommand::classRegex()[0])->toStartWith('@(?:base|option|compound|palette|variant|radius)\s*\(');
});

it('keeps existing VS Code settings and replaces an older Malevich entry', function () {
    $this->files->ensureDirectoryExists($this->vscode);
    $this->files->put($this->vscode.'/settings.json', json_encode([
        'editor.tabSize' => 4,
        'tailwindCSS.includeLanguages' => ['blade' => 'php'],
        'tailwindCSS.experimental.classRegex' => [['@(?:base|variant)\s*\(', 'x'], 'cva\(([^)]*)\)'],
    ]));

    $this->artisan('malevich:install', ['--components' => []])->expectsChoice('Which starter components do you want?', [], ['alert', 'badge', 'button', 'card', 'stat', 'switch'])->assertSuccessful();

    expect(vscodeSettings())->toBe([
        'editor.tabSize' => 4,
        'tailwindCSS.includeLanguages' => ['blade' => 'php'],
        'tailwindCSS.experimental.classRegex' => ['cva\(([^)]*)\)', InstallCommand::classRegex()],
    ]);
});

it('leaves VS Code settings that are not plain JSON alone', function () {
    $this->files->ensureDirectoryExists($this->vscode);
    $this->files->put($this->vscode.'/settings.json', "{\n  // comment\n}");

    $this->artisan('malevich:install', ['--components' => ['none']])
        ->expectsOutputToContain('not plain JSON')
        ->assertSuccessful();

    expect(file_get_contents($this->vscode.'/settings.json'))->toBe("{\n  // comment\n}");
});

it('can skip VS Code', function () {
    $this->artisan('malevich:install', ['--no-vscode' => true, '--components' => ['none']])->assertSuccessful();

    expect($this->vscode)->not->toBeDirectory();
});

it('asks which components to add', function () {
    $this->artisan('malevich:install', ['--no-vscode' => true])
        ->expectsChoice('Which starter components do you want?', ['card'], ['alert', 'badge', 'button', 'card', 'stat', 'switch'])
        ->assertSuccessful();

    expect($this->path.'/card.blade.php')->toBeFile()
        ->and($this->path.'/button.blade.php')->not->toBeFile();
});

it('adds every component with "all" and never overwrites without --force', function () {
    $this->files->ensureDirectoryExists($this->path);
    $this->files->put($this->path.'/button.blade.php', 'mine');

    $this->artisan('malevich:install', ['--no-vscode' => true, '--components' => ['all']])->assertSuccessful();

    expect($this->files->glob($this->path.'/*.blade.php'))->toHaveCount(6)
        ->and(file_get_contents($this->path.'/button.blade.php'))->toBe('mine');

    $this->artisan('malevich:install', ['--no-vscode' => true, '--components' => ['button'], '--force' => true])->assertSuccessful();

    expect(file_get_contents($this->path.'/button.blade.php'))->not->toBe('mine');
});

it('warns about unknown components', function () {
    $this->artisan('malevich:install', ['--no-vscode' => true, '--components' => ['nope']])
        ->expectsOutputToContain('Unknown starter components: nope')
        ->assertSuccessful();
});

it('does not overwrite a published config without --force', function () {
    $this->files->put($this->config, '<?php return [];');

    $this->artisan('malevich:install', ['--no-vscode' => true, '--components' => ['none']])->assertSuccessful();

    expect(file_get_contents($this->config))->toBe('<?php return [];');
});
