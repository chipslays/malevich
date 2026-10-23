<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Malevich\Malevich;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[AsCommand(name: 'malevich:install')]
class InstallCommand extends Command
{
    protected $signature = 'malevich:install
        {--no-vscode : Do not touch .vscode/settings.json}
        {--force : Overwrite an existing config/malevich.php}';

    protected $description = 'Set up Malevich: config and editor support';

    public function handle(Filesystem $files): int
    {
        $this->newLine();

        $this->publishConfig($files);

        if (! $this->option('no-vscode')) {
            $this->configureVsCode($files);
        }

        $this->reportClassMerging();

        $this->newLine();
        $this->line('  <fg=gray>Next:</>');
        $this->line('  - Add a kit component: <info>php artisan malevich:add button</info>');
        $this->line('  - Create a component: <info>php artisan make:malevich badge</info>');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function publishConfig(Filesystem $files): void
    {
        $exists = $files->exists(config_path('malevich.php'));

        if ($exists && ! $this->option('force')) {
            $this->components->twoColumnDetail('Config', '<fg=gray>config/malevich.php already exists</>');

            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'malevich:config', '--force' => true]);

        $this->components->twoColumnDetail('Config', '<fg=green>'.($exists ? 'overwritten' : 'published').'</> config/malevich.php');
    }

    /**
     * Teach Tailwind CSS IntelliSense where the classes are: inside @base,
     *
     * @variant & co., not only in class="...".
     */
    protected function configureVsCode(Filesystem $files): void
    {
        $path = base_path('.vscode/settings.json');
        $settings = [];

        if ($files->exists($path)) {
            $settings = json_decode($files->get($path), true);

            if (! is_array($settings)) {
                $this->components->twoColumnDetail('VS Code', '<fg=yellow>.vscode/settings.json is not plain JSON (comments?), left as is</>');
                $this->line('  Add this to it by hand:');
                $this->line(OutputFormatter::escape((string) json_encode(self::vsCodeSettings([]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));

                return;
            }
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, json_encode(self::vsCodeSettings($settings), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $this->components->twoColumnDetail('VS Code', '<fg=green>Tailwind IntelliSense configured</> .vscode/settings.json');
    }

    /**
     * The settings with Malevich's entries added, replacing an older Malevich
     * entry (e.g. one written before an option was added to the config).
     *
     * @param  array<array-key, mixed>  $settings
     * @return array<array-key, mixed>
     */
    public static function vsCodeSettings(array $settings): array
    {
        $languages = is_array($settings['tailwindCSS.includeLanguages'] ?? null) ? $settings['tailwindCSS.includeLanguages'] : [];
        $languages['blade'] ??= 'html';
        $settings['tailwindCSS.includeLanguages'] = $languages;

        $regex = is_array($settings['tailwindCSS.experimental.classRegex'] ?? null) ? $settings['tailwindCSS.experimental.classRegex'] : [];
        $regex = array_values(array_filter($regex, fn (mixed $entry) => ! (is_array($entry) && is_string($entry[0] ?? null) && str_starts_with($entry[0], '@(?:base|'))));
        $regex[] = self::classRegex();
        $settings['tailwindCSS.experimental.classRegex'] = $regex;

        return $settings;
    }

    /**
     * The first pattern finds a directive and its arguments, the second one
     * the strings inside that hold classes (values, not `'primary' =>` keys).
     *
     * @return array{0: string, 1: string}
     */
    public static function classRegex(): array
    {
        $names = array_unique([Malevich::directive('base'), Malevich::directive('option'), Malevich::directive('compound'), Malevich::directive('cases'), ...Malevich::options()]);

        return [
            '@(?:'.implode('|', $names).')\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\n|$)',
            '(?:^|=>|[\\[(,])\\s*["\']([^"\']*)["\'](?!\\s*=>)',
        ];
    }

    protected function reportClassMerging(): void
    {
        $installed = class_exists('TailwindMerge\Laravel\Facades\TailwindMerge') || class_exists('TailwindMerge\TailwindMerge');

        if ($installed && config('malevich.merge_classes', 'auto') === 'auto') {
            $this->components->twoColumnDetail('Tailwind conflicts', '<fg=green>resolved by tailwind-merge</>');

            return;
        }

        $this->components->twoColumnDetail('Tailwind conflicts', $installed ? '<fg=gray>merge_classes is off in config</>' : '<fg=gray>duplicates removed only</>');

        if (! $installed) {
            $this->line('  To let <comment>class="px-8"</comment> win over a component\'s <comment>px-4</comment>: <info>composer require gehrisandro/tailwind-merge-laravel</info>');
        }
    }
}
