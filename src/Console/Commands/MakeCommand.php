<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[AsCommand(name: 'make:malevich')]
class MakeCommand extends Command
{
    protected $signature = 'make:malevich
        {name : Component name, e.g. "button" or "forms/input"}
        {--force : Overwrite the component if it already exists}';

    protected $description = 'Create a new Malevich Blade component';

    public function handle(Filesystem $files): int
    {
        $name = str($this->argument('name'))
            ->replace('\\', '/')
            ->replaceEnd('.blade.php', '')
            ->replace('.', '/')
            ->replaceMatches('#/+#', '/')
            ->trim('/')
            ->value();

        // Letters, digits, "-", "_" and "/" only: the file must stay inside the components folder.
        if (! preg_match('#^[\w-]+(/[\w-]+)*$#', $name)) {
            $this->components->error('Invalid component name.');
            $this->line('  Use only letters, digits, <comment>-</comment>, <comment>_</comment> and <comment>/</comment>, e.g. <info>forms/input</info>.');
            $this->newLine();

            return self::FAILURE;
        }

        $path = rtrim((string) config('malevich.components.path'), '/\\').'/'.$name.'.blade.php';
        $exists = $files->exists($path);

        if ($exists && ! $this->option('force')) {
            $this->components->error('Component already exists.');
            $this->components->twoColumnDetail('File', $this->relative($path));
            $this->line('  Run again with <comment>--force</comment> to overwrite it.');
            $this->newLine();

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $this->render($files->get(__DIR__.'/../Stubs/component.stub')));

        $tag = OutputFormatter::escape('<'.Malevich::componentTag($name).'>');

        $this->components->info($exists ? 'Component overwritten.' : 'Component created.');
        $this->components->twoColumnDetail('File', $this->relative($path));
        $this->components->twoColumnDetail('Tag', "<fg=cyan>{$tag}</>");
        $this->components->twoColumnDetail('Options', implode(', ', array_map(
            fn (string $option) => "<fg=yellow>{$option}</>",
            Malevich::options(),
        )) ?: '<fg=gray>none</>');

        $this->newLine();
        $this->line('  <fg=gray>Next:</> fill in the <comment>@base</comment> classes, the maps for each option and their <comment>@defaults</comment>.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Path relative to the project root, with forward slashes, for readable output.
     */
    protected function relative(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    /**
     * Fill the stub with one empty class map and one commented-out default
     * per configured option, so custom options show up in new
     * components too. Defaults go to @defaults, not @props, so they never
     * shadow a preset.
     */
    protected function render(string $stub): string
    {
        $options = Malevich::options();

        $defaults = array_map(fn (string $option) => "    // '{$option}' => 'name',", $options);

        $maps = array_map(fn (string $option) => implode("\n", [
            "@{$option}([",
            "    // 'name' => 'classes',",
            '])',
        ]), $options);

        return str_replace(
            ['{{ options }}', '{{ defaults }}', '{{ render }}'],
            [implode("\n\n", $maps), implode("\n", $defaults), Malevich::renderDirective()],
            $stub,
        );
    }
}
