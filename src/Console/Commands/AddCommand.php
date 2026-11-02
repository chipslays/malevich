<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'malevich:add')]
class AddCommand extends Command
{
    protected $signature = 'malevich:add
        {name?* : Kit component names, e.g. "button"; omit to pick from a list}
        {--force : Overwrite components that already exist}';

    protected $description = 'Copy ready-made kit components into your project';

    /**
     * Kit components that render other kit components, added along with them.
     *
     * @var array<string, list<string>>
     */
    private const DEPENDENCIES = [
        'button/group' => ['button'],
        'input' => ['field'],
        'select' => ['field'],
        'slider' => ['field'],
        'tabs' => ['tabs/list', 'tabs/tab', 'tabs/panel'],
        'tabs/list' => ['tabs'],
        'tabs/tab' => ['tabs'],
        'tabs/panel' => ['tabs'],
    ];

    public function handle(Filesystem $files): int
    {
        $names = array_values(array_unique((array) $this->argument('name')));

        if ($names === []) {
            if (! $this->input->isInteractive()) {
                $this->components->error('Specify at least one component name.');

                return self::FAILURE;
            }

            $names = multiselect(
                label: 'Which kit components do you want to add?',
                options: self::available($files),
                required: true,
                hint: 'Space to select, Enter to confirm.',
            );
        }

        $names = array_map('strval', array_values($names));

        $status = self::SUCCESS;

        foreach ($names as $name) {
            if ($this->add($files, $name) !== self::SUCCESS) {
                $status = self::FAILURE;
            }
        }

        // Added after the requested components, and only when missing: a customized copy is never replaced.
        foreach (self::dependencies($names) as $dependency) {
            if ($files->exists(self::destination($dependency))) {
                continue;
            }

            $this->components->info("[{$dependency}] is required by the components above.");

            if ($this->add($files, $dependency) !== self::SUCCESS) {
                $status = self::FAILURE;
            }
        }

        // Kit components use the theme tokens; without them Tailwind skips their colors.
        if (! $files->exists(resource_path('css/vendor/malevich.css'))) {
            $this->newLine();
            $this->line('  <fg=gray>Next:</> publish the theme tokens the kit uses and import them after Tailwind:');
            $this->line('  <info>php artisan vendor:publish --tag=malevich:theme</info>');
            $this->line("  <info>@import './vendor/malevich.css';</info> <fg=gray>in resources/css/app.css</>");
        }

        return $status;
    }

    private function add(Filesystem $files, string $name): int
    {
        $name = Str::replaceEnd('.blade.php', '', str_replace('\\', '/', $name));

        if (str_contains($name, '..') || ! preg_match('#^[\w-]+(?:[/.][\w-]+)*$#D', $name)) {
            $this->components->error('Invalid component name.');

            return self::FAILURE;
        }

        $name = str_replace('.', '/', $name);
        $source = self::kit().'/'.$name.'.blade.php';

        if (! $files->isFile($source) && $files->isFile(self::kit().'/'.$name.'/index.blade.php')) {
            $source = self::kit().'/'.$name.'/index.blade.php';
        }

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

        $relative = substr($source, strlen(self::kit()) + 1);
        $path = rtrim($directory, '/\\').'/'.$relative;

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

        return self::SUCCESS;
    }

    private static function normalize(string $name): string
    {
        return Str::replaceEnd('/index', '', str_replace('.', '/', Str::replaceEnd('.blade.php', '', str_replace('\\', '/', $name))));
    }

    /**
     * Kit components the given ones need and that were not asked for themselves.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private static function dependencies(array $names): array
    {
        $requested = array_map(fn (string $name) => self::normalize($name), $names);
        $needed = [];
        $visited = [];
        $visit = function (string $name) use (&$visit, &$visited, &$needed, $requested): void {
            if (isset($visited[$name])) {
                return;
            }
            $visited[$name] = true;
            foreach (self::DEPENDENCIES[$name] ?? [] as $dependency) {
                if (! in_array($dependency, $requested, true)) {
                    $needed[$dependency] = $dependency;
                }
                $visit($dependency);
            }
        };
        foreach ($requested as $name) {
            $visit($name);
        }

        return array_values($needed);
    }

    private static function destination(string $name): string
    {
        $relative = is_file(self::kit().'/'.$name.'/index.blade.php') ? $name.'/index' : $name;

        return rtrim((string) config('malevich.components.path'), '/\\').'/'.$relative.'.blade.php';
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
                $name = substr(str_replace('\\', '/', $file->getRelativePathname()), 0, -10);
                $name = Str::replaceEnd('/index', '', $name);
                $available[] = str_replace('/', '.', $name);
            }
        }

        sort($available);

        return $available;
    }
}
