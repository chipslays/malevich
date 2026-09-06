<?php

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:malevich')]
class MakeCommand extends Command
{
    /**
     * Аргумент {name} определяет имя создаваемого файла.
     */
    protected $signature = 'make:malevich {name : The name of the component file (e.g. button)}';

    protected $description = 'Create a new component';

    /**
     * Используем встроенный в Laravel класс для работы с файловой системой.
     */
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');

        if (! str_ends_with($name, '.blade.php')) {
            $name .= '.blade.php';
        }

        $directory = config('malevich.components.path', resource_path('views/components/ui'));

        $destinationPath = $directory.'/'.$name;

        if ($this->files->exists($destinationPath)) {
            $this->error("Component {$name} already exists!");

            return Command::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($destinationPath));

        $stubPath = __DIR__.'/../Stubs/component.stub';

        if (! $this->files->exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");

            return Command::FAILURE;
        }

        $this->files->copy($stubPath, $destinationPath);

        $this->newLine(); // Добавляет пустую строку сверху для отступа

        // Заголовок успеха с инверсией цвета (белый текст на зеленом фоне)
        $this->line(" <bg=green;fg=white;options=bold> DONE </> Component <fg=green;options=bold>{$name}</> created successfully!");

        // Вывод пути с красивыми визуальными разделителями
        $this->line(" <fg=gray>└──</> <fg=yellow>📂 Destination:</> <fg=cyan>{$destinationPath}</>");
        $this->newLine(); // Отступ снизу

        return Command::SUCCESS;
    }
}
