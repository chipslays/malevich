<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Malevich\Console\Commands\MakeCommand;
use Malevich\Support\ComponentTagCompiler;

class MalevichServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/malevich.php', 'malevich');
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

            $this->commands([MakeCommand::class]);
        }

        $this->registerBladeDirectives();
        $this->registerAttributeBagMacros();
        $this->registerComponentPath();
    }

    protected function registerBladeDirectives(): void
    {
        $this->ensureValidNames();

        // The recipe is also kept in a template variable, so @ui still finds it
        // after Blade swaps $attributes for a new bag (e.g. inside <x-...> tags).
        $recipe = '($'.Malevich::SCOPE_VARIABLE.' ??= \\'.Malevich::class.'::recipe($attributes))';

        Blade::directive('directive', fn (string $expression) => "<?php {$recipe}->axis({$expression}); ?>");
        Blade::directive('base', fn (string $expression) => "<?php {$recipe}->base({$expression}); ?>");
        Blade::directive('compound', fn (string $expression) => "<?php {$recipe}->compound({$expression}); ?>");
        Blade::directive('preset', fn (string $expression) => "<?php {$recipe}->preset({$expression}); ?>");

        foreach ($this->axes() as $axis) {
            Blade::directive($axis, fn (string $expression) => "<?php {$recipe}->axis('{$axis}', {$expression}); ?>");
        }

        Blade::directive(Malevich::renderDirective(), function (string $expression) {
            $arguments = trim($expression) === '' ? '' : ", {$expression}";

            return '<?php echo \\'.Malevich::class."::ui(\$attributes, get_defined_vars(){$arguments}); ?>";
        });

        // Blade ignores directives inside <x-...> tags, so @ui is rewritten there first.
        Blade::prepareStringsForCompilationUsing(ComponentTagCompiler::compile(...));
    }

    /**
     * Fail loudly on names from the config that would silently break things:
     * a custom Blade directive replaces a built-in one (`@if`, `@class`, ...)
     * for the whole application, and a name that is already a method of
     * $attributes (`merge`, `get`, ...) can never work as a fluent call.
     */
    protected function ensureValidNames(): void
    {
        $render = Malevich::renderDirective();
        $own = ['directive', 'base', 'compound', 'preset'];

        foreach ([$render, ...$this->axes()] as $name) {
            $problem = match (true) {
                ! preg_match('/^[A-Za-z_]\w*$/', $name) => 'is not a valid directive name',
                in_array($name, $own, true) => 'is already a Malevich directive',
                method_exists(BladeCompiler::class, 'compile'.ucfirst($name)) => 'is a built-in Blade directive',
                $name !== $render && method_exists(ComponentAttributeBag::class, $name) => 'is already a method of $attributes',
                default => null,
            };

            if ($problem !== null) {
                throw new InvalidArgumentException("Malevich: [@{$name}] {$problem}. Pick another name in config/malevich.php.");
            }
        }

        if (in_array($render, $this->axes(), true)) {
            throw new InvalidArgumentException("Malevich: [@{$render}] is used both as render_directive and in directives. Pick another name in config/malevich.php.");
        }
    }

    protected function registerAttributeBagMacros(): void
    {
        $this->macro('for', fn (Element $element, string $target) => $element->for($target));
        $this->macro('use', fn (Element $element, mixed ...$arguments) => $element->use(...$arguments));
        $this->macro('directive', fn (Element $element, string $axis, mixed $value = null) => $element->directive($axis, $value));
        $this->macro('preset', fn (Element $element, ?string $name) => $element->preset($name));

        foreach ($this->axes() as $axis) {
            $this->macro($axis, fn (Element $element, mixed $value = null) => $element->directive($axis, $value));
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

        ComponentAttributeBag::macro($name, function (mixed ...$arguments) use ($callback): Element {
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
    protected function axes(): array
    {
        return Malevich::directives();
    }
}
