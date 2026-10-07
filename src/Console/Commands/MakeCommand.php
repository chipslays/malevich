<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;

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
            ->trim('/')
            ->value();

        if ($name === '') {
            $this->components->error('Please provide a component name.');

            return self::FAILURE;
        }

        $path = rtrim((string) config('malevich.components.path'), '/\\').'/'.$name.'.blade.php';

        if ($files->exists($path) && ! $this->option('force')) {
            $this->components->error("Component [{$path}] already exists. Use --force to overwrite it.");

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $this->render($files->get(__DIR__.'/../Stubs/component.stub')));

        $this->components->info(sprintf('Component [%s] created. Use it as <%s>.', $path, Malevich::componentTag($name)));

        return self::SUCCESS;
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
