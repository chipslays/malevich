<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;

use function Laravel\Prompts\select;

#[AsCommand(name: 'malevich:add')]
class AddCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'malevich:add
        {name : Kit component name, e.g. "button"}
        {--force : Overwrite the component if it already exists}';

    protected $description = 'Copy a ready-made kit component into your project';

    public function handle(Filesystem $files): int
    {
        $name = Str::replaceEnd('.blade.php', '', str_replace('\\', '/', (string) $this->argument('name')));

        if (str_contains($name, '..') || ! preg_match('#^[\w-]+(?:[/.][\w-]+)*$#D', $name)) {
            $this->components->error('Invalid component name.');

            return self::FAILURE;
        }

        $name = str_replace('.', '/', $name);
        $source = self::kit().'/'.$name.'.blade.php';

        if (! $files->isFile($source)) {
            $this->components->error("Kit component [{$name}] does not exist.");
            $this->line('Available: '.OutputFormatter::escape(implode(', ', self::available($files))));

            return self::FAILURE;
        }

        $directory = config('malevich.components.path');

        if (! is_string($directory) || trim($directory) === '') {
            $this->components->error('Set malevich.components.path to your components directory.');

            return self::FAILURE;
        }

        $path = rtrim($directory, '/\\').'/'.$name.'.blade.php';

        if ($files->isDirectory($path)) {
            $this->components->error('A directory already exists at the component path.');

            return self::FAILURE;
        }

        $exists = $files->exists($path);

        if ($exists && ! $this->option('force')) {
            $this->components->error('Component already exists.');
            $this->line('Run again with <comment>--force</comment> to overwrite it.');

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));

        if (! $files->copy($source, $path)) {
            $this->components->error('Could not copy the kit component.');

            return self::FAILURE;
        }

        $this->components->info($exists ? 'Kit component overwritten.' : 'Kit component added.');
        $this->components->twoColumnDetail('File', OutputFormatter::escape($path));
        $this->components->twoColumnDetail('Tag', OutputFormatter::escape('<'.Malevich::componentTag($name).'>'));

        // Kit components use the theme tokens; without them Tailwind skips their colors.
        if (! $files->exists(resource_path('css/malevich-theme.css'))) {
            $this->newLine();
            $this->line('  <fg=gray>Next:</> publish the theme tokens the kit uses and import them after Tailwind:');
            $this->line('  <info>php artisan vendor:publish --tag=malevich:theme</info>');
            $this->line("  <info>@import './malevich-theme.css';</info> <fg=gray>in resources/css/app.css</>");
        }

        return self::SUCCESS;
    }

    /**
     * Without a name, pick one of the kit's components from a list.
     *
     * @return array<string, Closure(): string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => fn (): string => (string) select(
                label: 'Which kit component do you want to add?',
                options: self::available(new Filesystem),
            ),
        ];
    }

    private static function kit(): string
    {
        return dirname(__DIR__, 3).'/resources/kit';
    }

    /**
     * Names of every kit component, e.g. `button` or `forms/input`.
     *
     * @return list<string>
     */
    private static function available(Filesystem $files): array
    {
        $available = [];

        foreach ($files->allFiles(self::kit()) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $available[] = substr(str_replace('\\', '/', $file->getRelativePathname()), 0, -10);
            }
        }

        sort($available);

        return $available;
    }
}
