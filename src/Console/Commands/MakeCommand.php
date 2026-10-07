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
        $this->components->twoColumnDetail('Directives', implode(', ', array_map(
            fn (string $directive) => "<fg=yellow>{$directive}</>",
            Malevich::directives(),
        )) ?: '<fg=gray>none</>');

        $this->newLine();
        $this->line('  <fg=gray>Next:</> fill in the <comment>@base</comment> classes and the maps for each directive.');
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
     * Fill the stub with one prop and one empty class map per configured
     * directive, so custom directives show up in new components too.
     */
    protected function render(string $stub): string
    {
        $directives = Malevich::directives();

        $props = array_map(fn (string $directive) => "    '{$directive}' => null,", $directives);

        $maps = array_map(fn (string $directive) => implode("\n", [
            "@{$directive}([",
            "    // 'name' => 'classes',",
            '])',
        ]), $directives);

        return str_replace(
            ['{{ props }}', '{{ directives }}', '{{ render }}'],
            [implode("\n", $props), implode("\n\n", $maps), Malevich::renderDirective()],
            $stub,
        );
    }
}
