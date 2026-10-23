<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Malevich\Console\Commands\AddCommand;
use Malevich\Console\Commands\InstallCommand;
use Malevich\Console\Commands\LintCommand;
use Malevich\Console\Commands\MakeCommand;
use Malevich\Playground\Playground;
use Malevich\Support\ComponentTagCompiler;
use Malevich\Support\HtmlTagCompiler;
use Malevich\Support\ScopedProps;
use Malevich\Support\TargetAttribute;
use Malevich\Testing\ClassAssertions;
use WeakMap;

class MalevichServiceProvider extends ServiceProvider
{
    /**
     * Directive names Malevich registered, per Blade compiler. Tells our own
     * directives apart from a clash with another package when the provider
     * boots again.
     *
     * @var WeakMap<object, array<string, true>>|null
     */
    private static ?WeakMap $registered = null;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/malevich.php', 'malevich');

        $this->app->singleton(MalevichManager::class, fn ($app) => new MalevichManager($app['config']));
        $this->app->alias(MalevichManager::class, 'malevich');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/malevich.php' => config_path('malevich.php'),
            ], 'malevich:config');

            $this->publishes([
                __DIR__.'/../resources/views/components' => resource_path('views/vendor/malevich/components'),
            ], 'malevich:components');

            $this->publishes([
                __DIR__.'/../resources/kit/theme.css' => resource_path('css/malevich-theme.css'),
            ], 'malevich:theme');

            $this->commands([AddCommand::class, InstallCommand::class, LintCommand::class, MakeCommand::class]);

            // Tests run in the console too: ->assertSeeClasses() and expect()->toHaveClasses().
            ClassAssertions::register();
        }

        $this->registerBladeDirectives();
        $this->registerAttributeBagMacros();
        $this->registerComponentPath();

        // After every provider has booted, so Malevich::playground() in the app's provider counts.
        $this->app->booted(function () {
            if (Playground::enabled() && ! App::routesAreCached()) {
                Playground::routes();
            }
        });
    }

    protected function registerBladeDirectives(): void
    {
        Malevich::flushConfig();
        $this->ensureValidNames();

        // The recipe is also kept in a template variable, so @attributes still finds it
        // after Blade swaps $attributes for a new bag (e.g. inside <x-...> tags).
        $recipe = '($'.MalevichManager::SCOPE_VARIABLE.' ??= \\'.Malevich::class.'::recipe($attributes ?? null))';

        // Each declaration calls the Recipe method of its role, whatever the directive is named.
        foreach (['option', 'base', 'compound', 'cases'] as $role) {
            $this->declaration(Malevich::directive($role), fn (string $expression) => "<?php {$recipe}->{$role}({$expression}); ?>");
        }

        foreach ($this->options() as $option) {
            $this->declaration($option, fn (string $expression) => "<?php {$recipe}->option('{$option}', {$expression}); ?>");
        }

        $this->registerDirective(Malevich::directive('attributes'), function (string $expression) {
            $arguments = trim($expression) === '' ? '' : ", {$expression}";

            return '<?php echo \\'.Malevich::class."::attributes(\$attributes ?? null, get_defined_vars(){$arguments}); ?>";
        });

        $this->registerDirective(Malevich::directive('styled'), function (string $expression) {
            $arguments = trim($expression) === '' ? '' : ", {$expression}";

            return '<?php if (\\'.Malevich::class."::styled(\$attributes ?? null, get_defined_vars(){$arguments})): ?>";
        });

        // `@end` + the name, like @endauth: the closing tag is a plain endif.
        $this->registerDirective('end'.Malevich::directive('styled'), fn () => '<?php endif; ?>');

        Blade::prepareStringsForCompilationUsing(TargetAttribute::compile(...));
        Blade::precompiler(TargetAttribute::restore(...));
        Blade::prepareStringsForCompilationUsing(ScopedProps::compile(...));

        // Blade ignores directives inside <x-...> tags, so @attributes is rewritten there first.
        Blade::prepareStringsForCompilationUsing(ComponentTagCompiler::compile(...));
        // On plain tags a `class="..."` next to @attributes would become a second class attribute.
        Blade::prepareStringsForCompilationUsing(HtmlTagCompiler::compile(...));
    }

    /**
     * A directive that only makes sense with an expression: `@variant([...])`.
     * Without parentheses it is not ours, so the text is left alone. That keeps
     * CSS inside a template intact, e.g. Tailwind's own `@variant dark`.
     *
     * @param  Closure(string): string  $compile
     */
    private function declaration(string $name, Closure $compile): void
    {
        $this->registerDirective($name, fn (string $expression) => $expression === '' ? "@{$name}" : $compile($expression));
    }

    /**
     * @param  Closure(string): string  $compile
     */
    private function registerDirective(string $name, Closure $compile): void
    {
        Blade::directive($name, $compile);

        self::$registered ??= new WeakMap;
        $compiler = Blade::getFacadeRoot();
        $names = self::$registered[$compiler] ?? [];
        $names[$name] = true;
        self::$registered[$compiler] = $names;
    }

    /**
     * Fail loudly on names that would silently break things: a custom Blade
     * directive replaces a built-in one (`@if`, `@class`, ...) or a directive of
     * another package for the whole application, and a name that is already a
     * method of $attributes (`merge`, `get`, ...) can never work as a fluent call.
     */
    protected function ensureValidNames(): void
    {
        // name => the config key it comes from
        $names = [];

        foreach (Malevich::directives() as $role => $name) {
            if (isset($names[$name])) {
                throw new InvalidArgumentException("Malevich: [@{$name}] is used both as {$names[$name]} and directives.{$role}. Pick another name in config/malevich.php.");
            }

            $names[$name] = "directives.{$role}";
        }

        // The closing tag of the styled-directive is a name of its own.
        $names['end'.Malevich::directive('styled')] = 'directives.styled';

        foreach ($this->options() as $name) {
            if (isset($names[$name]) && $names[$name] !== 'options') {
                throw new InvalidArgumentException("Malevich: [@{$name}] is used both as {$names[$name]} and in options. Pick another name in config/malevich.php.");
            }

            $names[$name] = 'options';
        }

        foreach ($names as $name => $key) {
            $problem = match (true) {
                ! preg_match('/^[A-Za-z_]\w*$/', $name) => 'is not a valid directive name',
                $this->isBuiltIn($name) => 'is a built-in Blade directive',
                $this->isTakenByAnotherPackage($name) => 'is already registered as a Blade directive by your application or another package',
                $key === 'options' && method_exists(ComponentAttributeBag::class, $name) => 'is already a method of $attributes',
                default => null,
            };

            if ($problem !== null) {
                throw new InvalidArgumentException("Malevich: [@{$name}] {$problem}. Pick another name in config/malevich.php.");
            }
        }
    }

    private function isBuiltIn(string $name): bool
    {
        return method_exists(BladeCompiler::class, 'compile'.ucfirst($name));
    }

    private function isTakenByAnotherPackage(string $name): bool
    {
        $mine = self::$registered[Blade::getFacadeRoot()] ?? [];

        return array_key_exists($name, Blade::getCustomDirectives()) && ! isset($mine[$name]);
    }

    protected function registerAttributeBagMacros(): void
    {
        $this->macro('for', fn (Element $element, string $target) => $element->for($target));
        $this->macro('use', fn (Element $element, mixed ...$arguments) => $element->use(...$arguments));
        $this->macro('option', fn (Element $element, string $option, mixed $value = null) => $element->option($option, $value));
        $this->macro('classesFor', fn (Element $element, string $option, ?array $values = null) => $element->classesFor($option, $values));

        foreach ($this->options() as $option) {
            $this->macro($option, fn (Element $element, mixed $value = null) => $element->option($option, $value));
        }
    }

    /**
     * Add `$attributes->{$name}(...)`, which starts a fresh Element for the
     * bag. Never shadows a macro the application registered itself.
     *
     * @param  Closure  $callback  Receives the new Element and the macro arguments.
     */
    protected function macro(string $name, Closure $callback): void
    {
        if (ComponentAttributeBag::hasMacro($name)) {
            return;
        }

        ComponentAttributeBag::macro($name, function (mixed ...$arguments) use ($callback): mixed {
            return $callback(new Element($this), ...$arguments);
        });
    }

    /**
     * Register the package components as `<x-malevich::...>`, and the
     * application's components directory as `<x-button>` (or
     * `<x-ui::button>` when a prefix is configured).
     */
    protected function registerComponentPath(): void
    {
        $path = config('malevich.components.path');

        // A view namespace, not an anonymous path: Blade searches anonymous paths
        // even for unprefixed tags, so <x-button> in the app would hit ours.
        // loadViewsFrom() also checks resources/views/vendor/malevich first,
        // which is where `vendor:publish --tag malevich:components` puts copies.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'malevich');
        Blade::anonymousComponentNamespace('malevich::components', 'malevich');

        if (is_string($path) && is_dir($path)) {
            Blade::anonymousComponentPath($path, config('malevich.components.prefix') ?: null);
        }
    }

    /**
     * @return list<string>
     */
    protected function options(): array
    {
        return Malevich::options();
    }
}
