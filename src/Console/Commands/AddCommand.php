<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[AsCommand(name: 'malevich:add')]
class AddCommand extends Command
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
        $kit = dirname(__DIR__, 3).'/resources/kit';
        $source = $kit.'/'.$name.'.blade.php';

        if (! $files->isFile($source)) {
            $this->components->error("Kit component [{$name}] does not exist.");
            $available = [];

            foreach ($files->allFiles($kit) as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $available[] = substr(str_replace('\\', '/', $file->getRelativePathname()), 0, -10);
                }
            }

            sort($available);
            $this->line('Available: '.OutputFormatter::escape(implode(', ', $available)));

            return self::FAILURE;
        }

        $directory = config('malevich.components.path');

        if (! is_string($directory) || trim($directory) === '') {
            $this->components->error('Set malevich.components.path to your components directory.');

            return self::FAILURE;
        }

        $path = rtrim($directory, '/\\').'/'.$name.'.blade.php';
        $exists = $files->exists($path);

        if ($files->isDirectory($path)) {
            $this->components->error('A directory already exists at the component path.');

            return self::FAILURE;
        }

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

        return self::SUCCESS;
    }
}
