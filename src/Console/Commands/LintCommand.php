<?php

declare(strict_types=1);

namespace Malevich\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use Malevich\Support\ComponentScanner;
use Malevich\Support\TagScanner;
use Malevich\Support\TargetAttribute;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'malevich:lint')]
class LintCommand extends Command
{
    protected $signature = 'malevich:lint
        {paths?* : Files or folders to check (default: the view paths)}
        {--format=text : Output format: text or github (annotations for GitHub Actions)}';

    protected $description = 'Find option values your Malevich components do not define, e.g. <x-button variant="primay">';

    public function handle(Filesystem $files): int
    {
        $finder = app('view')->getFinder();

        if (! $finder instanceof FileViewFinder) {
            $this->components->error('malevich:lint needs Laravel\'s file view finder to locate components.');

            return self::FAILURE;
        }

        $components = (new ComponentScanner($files, $finder))->components();
        $problems = [];
        $checked = 0;

        foreach ($this->views($files) as $path) {
            $template = $files->get($path);

            if (! str_contains($template, '<x-')) {
                continue;
            }

            foreach (TagScanner::findTags($template, 'x-[\w\-:.]+') as $tag) {
                $name = substr($tag['name'], 2);
                $component = $components[$name] ?? null;

                if ($component === null) {
                    continue;
                }

                $checked++;

                foreach ($this->problems($component, TagScanner::staticAttributes($tag['attributes'])) as $problem) {
                    $problems[] = [
                        'file' => $path,
                        'line' => substr_count($template, "\n", 0, $tag['offset']) + 1,
                        'tag' => $tag['name'],
                        'message' => $problem,
                    ];
                }
            }
        }

        $this->option('format') === 'github' ? $this->github($problems) : $this->text($problems, $checked, count($components));

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array{file: string, options: array<string, list<string>>, targets?: list<string>}  $component
     * @param  array<string, string>  $attributes
     * @return list<string>
     */
    protected function problems(array $component, array $attributes): array
    {
        $problems = [];

        foreach ($attributes as $attribute => $value) {
            $targeted = TargetAttribute::parse($attribute);
            $option = $targeted !== null && in_array($targeted['target'], $component['targets'] ?? [], true)
                ? Str::camel($targeted['name'])
                : Str::camel($attribute);

            $known = $component['options'][$option] ?? $component['options'][$attribute] ?? null;

            if ($known === null && $targeted !== null) {
                $known = $component['options'][TargetAttribute::propName($targeted)] ?? null;
            }

            // The missing side of a boolean (`['true' => ...]` and loading="false") is fine, as in strict mode.
            $boolean = in_array($value, ['true', 'false'], true) && array_intersect($known ?? [], ['true', 'false']) !== [];

            if ($known !== null && ! $boolean && ! in_array($value, $known, true)) {
                $problems[] = self::message($attribute, $value, $known);
            }
        }

        return $problems;
    }

    /**
     * @param  list<string>  $known
     */
    private static function message(string $option, string $value, array $known): string
    {
        $suggestion = self::closest($value, $known);

        return "{$option} [{$value}] is not defined; expected one of: ".implode(', ', $known).'.'
            .($suggestion === null ? '' : " Did you mean [{$suggestion}]?");
    }

    /**
     * @param  list<string>  $known
     */
    private static function closest(string $value, array $known): ?string
    {
        $best = null;
        $distance = PHP_INT_MAX;

        foreach ($known as $candidate) {
            $current = levenshtein(strtolower($value), strtolower($candidate));

            if ($current < $distance) {
                [$best, $distance] = [$candidate, $current];
            }
        }

        return $distance <= max(1, intdiv(strlen($value), 3)) ? $best : null;
    }

    /**
     * Blade files to check, each once.
     *
     * @return list<string>
     */
    protected function views(Filesystem $files): array
    {
        /** @var list<string> $paths */
        $paths = $this->argument('paths') ?: (array) config('view.paths', []);
        $views = [];

        foreach ($paths as $path) {
            $path = realpath($path) ?: $path;

            $found = is_file($path) ? [$path] : (is_dir($path) ? array_map(fn ($file) => $file->getPathname(), $files->allFiles($path)) : []);

            foreach ($found as $file) {
                if (str_ends_with($file, '.blade.php')) {
                    $views[realpath($file) ?: $file] = true;
                }
            }
        }

        $views = array_keys($views);
        sort($views);

        return $views;
    }

    /**
     * @param  list<array{file: string, line: int, tag: string, message: string}>  $problems
     */
    protected function text(array $problems, int $checked, int $components): void
    {
        $this->newLine();

        foreach ($problems as $problem) {
            $this->line('  <fg=red>✗</> '.$this->relative($problem['file']).':'.$problem['line'].'  <fg=cyan>'.OutputFormatter::escape('<'.$problem['tag'].'>').'</>');
            $this->line('    '.OutputFormatter::escape($problem['message']));
        }

        if ($problems !== []) {
            $this->newLine();
            $this->components->error(count($problems).' '.Str::plural('problem', count($problems))." in {$checked} ".Str::plural('tag', $checked).' of '.$components.' '.Str::plural('component', $components).'.');

            return;
        }

        $this->components->info("No problems: {$checked} ".Str::plural('tag', $checked).' of '.$components.' '.Str::plural('component', $components).' checked.');
    }

    /**
     * @param  list<array{file: string, line: int, tag: string, message: string}>  $problems
     */
    protected function github(array $problems): void
    {
        foreach ($problems as $problem) {
            $message = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], "<{$problem['tag']}>: {$problem['message']}");

            $this->output->writeln("::error file={$this->relative($problem['file'])},line={$problem['line']}::{$message}", OutputInterface::OUTPUT_RAW);
        }
    }

    private function relative(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
