<?php

use Illuminate\Filesystem\Filesystem;
use Malevich\Console\Commands\InstallCommand;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->config = config_path('malevich.php');
    $this->vscode = base_path('.vscode');

    // The testbench app is shared by every test: never leave files behind in it.
    expect($this->config)->not->toBeFile()
        ->and($this->vscode)->not->toBeDirectory();
});

afterEach(function () {
    $this->files->deleteDirectory($this->vscode);
    $this->files->delete($this->config);
});

function vscodeSettings(): array
{
    return json_decode(file_get_contents(base_path('.vscode/settings.json')), true);
}

it('publishes the config and configures VS Code', function () {
    $this->artisan('malevich:install')->assertSuccessful();

    expect($this->config)->toBeFile()
        ->and(vscodeSettings())->toBe([
            'tailwindCSS.includeLanguages' => ['blade' => 'html'],
            'tailwindCSS.experimental.classRegex' => [InstallCommand::classRegex()],
        ]);
});

it('creates no components', function () {
    $path = sys_get_temp_dir().'/malevich-'.uniqid();
    config(['malevich.components.path' => $path]);

    $this->artisan('malevich:install')->assertSuccessful();

    expect($path)->not->toBeDirectory();
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

    $this->artisan('malevich:install')->assertSuccessful();

    expect(vscodeSettings())->toBe([
        'editor.tabSize' => 4,
        'tailwindCSS.includeLanguages' => ['blade' => 'php'],
        'tailwindCSS.experimental.classRegex' => ['cva\(([^)]*)\)', InstallCommand::classRegex()],
    ]);
});

it('leaves VS Code settings that are not plain JSON alone', function () {
    $this->files->ensureDirectoryExists($this->vscode);
    $this->files->put($this->vscode.'/settings.json', "{\n  // comment\n}");

    $this->artisan('malevich:install')
        ->expectsOutputToContain('not plain JSON')
        ->assertSuccessful();

    expect(file_get_contents($this->vscode.'/settings.json'))->toBe("{\n  // comment\n}");
});

it('can skip VS Code', function () {
    $this->artisan('malevich:install', ['--no-vscode' => true])->assertSuccessful();

    expect($this->vscode)->not->toBeDirectory();
});

it('does not overwrite a published config without --force', function () {
    $this->files->put($this->config, '<?php return [];');

    $this->artisan('malevich:install', ['--no-vscode' => true])->assertSuccessful();

    expect(file_get_contents($this->config))->toBe('<?php return [];');

    $this->artisan('malevich:install', ['--no-vscode' => true, '--force' => true])->assertSuccessful();

    expect(file_get_contents($this->config))->not->toBe('<?php return [];');
});
