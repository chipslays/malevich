<?php

declare(strict_types=1);

namespace Malevich\Catalog;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use LogicException;
use Malevich\Malevich;
use Malevich\Support\ComponentScanner;
use Throwable;

/**
 * The component catalog: every component of the app rendered with every
 * value of its options. Built from the same static scan as malevich:lint,
 * so it needs no setup - a component shows up as soon as it declares a
 * class map.
 *
 * @internal
 */
final class Catalog
{
    public function __construct(
        private readonly ComponentScanner $scanner,
        private readonly Filesystem $files,
    ) {}

    public static function make(): self
    {
        $finder = app('view')->getFinder();

        if (! $finder instanceof FileViewFinder) {
            throw new LogicException('The Malevich catalog needs Laravel\'s file view finder to locate components.');
        }

        return new self(new ComponentScanner(new Filesystem, $finder), new Filesystem);
    }

    public static function enabled(): bool
    {
        $enabled = config('malevich.catalog.enabled');

        return $enabled === null || $enabled === '' ? app()->isLocal() : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function routes(): void
    {
        $path = trim((string) config('malevich.catalog.path', 'malevich'), '/');

        Route::middleware((array) config('malevich.catalog.middleware', ['web']))
            ->get($path, CatalogController::class)
            ->name('malevich.catalog');
    }

    /**
     * The application's components with options, by tag name without `x-`.
     * The package's own components are left out.
     *
     * @return array<string, array{file: string, options: array<string, list<string>>}>
     */
    public function components(): array
    {
        $components = [];

        foreach ($this->scanner->components() as $name => $component) {
            if (! str_starts_with($name, 'malevich::')) {
                $components[$name] = ['file' => $component['file'], 'options' => $this->ordered($component['options'])];
            }
        }

        return $components;
    }

    /**
     * Everything the page shows for one component: the default rendering,
     * one row per option, and a matrix of the first two options.
     *
     * @param  array{file: string, options: array<string, list<string>>}  $component
     * @return array{default: Cell, options: array<string, list<Cell>>, matrix: array{rows: string, columns: string, cells: array<string, array<string, Cell>>}|null}
     */
    public function sheet(string $name, array $component): array
    {
        $slot = $this->example($component['file']) ?? Str::headline((string) Str::afterLast(Str::afterLast($name, '::'), '.'));
        $options = [];

        foreach ($component['options'] as $option => $values) {
            foreach ($values as $value) {
                $options[$option][] = $this->cell($name, [$option => $value], $slot);
            }
        }

        $matrix = null;
        $names = array_keys($component['options']);

        if (count($names) >= 2) {
            [$rows, $columns] = $names;
            $cells = [];

            foreach ($component['options'][$rows] as $row) {
                foreach ($component['options'][$columns] as $column) {
                    $cells[$row][$column] = $this->cell($name, [$rows => $row, $columns => $column], $slot);
                }
            }

            $matrix = ['rows' => $rows, 'columns' => $columns, 'cells' => $cells];
        }

        return [
            'default' => $this->cell($name, [], $slot),
            'options' => $options,
            'matrix' => $matrix,
        ];
    }

    /**
     * The tag for these option values, rendered.
     *
     * @param  array<string, string>  $values
     */
    public function cell(string $name, array $values, ?string $slot): Cell
    {
        $attributes = '';

        foreach ($values as $option => $value) {
            $attributes .= match ($value) {
                'true' => " {$option}",
                'false' => " :{$option}=\"false\"",
                default => " {$option}=\"".e($value).'"',
            };
        }

        $tag = $slot === null || trim($slot) === ''
            ? "<x-{$name}{$attributes} />"
            : "<x-{$name}{$attributes}>{$slot}</x-{$name}>";

        try {
            return new Cell($tag, $values, trim(Blade::render($tag)));
        } catch (Throwable $e) {
            return new Cell($tag, $values, null, $e->getPrevious()?->getMessage() ?? $e->getMessage());
        }
    }

    /**
     * The content of the usage example in the component's leading comment:
     *
     *     {{-- <x-alert color="red"><x-slot:title>Oops</x-slot:title>Declined.</x-alert> --}}
     *
     * An empty string for a self-closing example, null without one.
     */
    public function example(string $file): ?string
    {
        if (! preg_match('/^\s*\{\{--(.*?)--\}\}/s', $this->files->get($file), $comment)) {
            return null;
        }

        if (! preg_match('/<x-([\w\-:.]+)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*?(\/>|>(.*?)<\/x-\1\s*>)/s', $comment[1], $tag)) {
            return null;
        }

        $slot = $tag[3] ?? '';

        return $tag[2] === '/>' ? '' : trim(preg_replace('/^[ \t]+/m', '', $slot) ?? $slot);
    }

    /**
     * What goes into the page's <head> to load the app's CSS.
     */
    public function head(): string
    {
        $head = config('malevich.catalog.head');

        if (is_string($head)) {
            return Blade::render($head);
        }

        $vite = is_file(public_path('hot')) || is_file(public_path('build/manifest.json'));
        $entries = self::viteEntries(base_path());

        if (! $vite || $entries === []) {
            return '';
        }

        return Blade::render('@vite(['.implode(', ', array_map(fn (string $entry) => var_export($entry, true), $entries)).'])');
    }

    /**
     * The project's own Vite entry points - the same CSS (with its theme,
     * custom colors and fonts) and JS (Alpine, ...) the app's pages load.
     * Read from `input` in vite.config; without it, the Laravel defaults
     * that exist.
     *
     * @return list<string>
     */
    public static function viteEntries(string $base): array
    {
        $entries = [];

        foreach (['js', 'ts', 'mjs', 'mts', 'cjs'] as $extension) {
            $config = "{$base}/vite.config.{$extension}";

            if (is_file($config) && preg_match('/\binput\s*:\s*(\[[^\]]*\]|\'[^\']*\'|"[^"]*")/', (string) file_get_contents($config), $input)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $input[1], $strings);
                $entries = $strings[1];

                break;
            }
        }

        $entries = $entries === [] ? ['resources/css/app.css', 'resources/js/app.js'] : $entries;

        return array_values(array_filter($entries, fn (string $entry) => is_file("{$base}/{$entry}")));
    }

    /**
     * The configured options first (variant, size, color), then the rest.
     *
     * @param  array<string, list<string>>  $options
     * @return array<string, list<string>>
     */
    private function ordered(array $options): array
    {
        $order = array_flip(Malevich::options());

        uksort($options, fn (string $a, string $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);

        return $options;
    }
}
