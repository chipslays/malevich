<?php

/*
 * Render the same button N times as a Malevich component and as a
 * hand-written Blade component, and print the cost per component.
 *
 *     composer bench
 *     php -d opcache.enable_cli=1 -d opcache.file_update_protection=0 benchmarks/run.php 5000
 *
 * opcache.file_update_protection=0 matters: by default opcache skips files
 * younger than 2 seconds, so freshly compiled views would be parsed again
 * on every include and the numbers would be several times too high.
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Malevich\MalevichServiceProvider;
use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

Application::create(options: ['extra' => ['providers' => [MalevichServiceProvider::class]]]);
Blade::anonymousComponentPath(__DIR__.'/components', 'bench');

$count = max(1, (int) ($argv[1] ?? 2000));
$rounds = 5;
$results = [];

foreach (['plain-button', 'malevich-button'] as $component) {
    $template = "@foreach (range(1, {$count}) as \$i)<x-bench::{$component} variant=\"outline\" size=\"lg\" class=\"mt-2\">Go</x-bench::{$component}>@endforeach";

    Blade::render($template); // compile and warm up

    for ($round = 0; $round < $rounds; $round++) {
        $start = hrtime(true);
        Blade::render($template);
        $results[$component][] = (hrtime(true) - $start) / 1e3 / $count;
    }
}

$plain = min($results['plain-button']);
$malevich = min($results['malevich-button']);

printf("%d components, best of %d rounds\n\n", $count, $rounds);
printf("  plain Blade   %6.1f µs per component\n", $plain);
printf("  Malevich      %6.1f µs per component  (%+.0f%%)\n", $malevich, ($malevich / $plain - 1) * 100);
