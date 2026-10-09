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
use Malevich\Support\HtmlTagCompiler;
use WeakMap;

class MalevichServiceProvider extends ServiceProvider
{
    /**
     * Directives with a fixed name. Everything else is configurable.
     */
    private const FIXED = ['directive', 'base', 'compound', 'preset'];

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

        $this->declaration('directive', fn (string $expression) => "<?php {$recipe}->axis({$expression}); ?>");
        $this->declaration('base', fn (string $expression) => "<?php {$recipe}->base({$expression}); ?>");
        $this->declaration('compound', fn (string $expression) => "<?php {$recipe}->compound({$expression}); ?>");
        $this->declaration('preset', fn (string $expression) => "<?php {$recipe}->preset({$expression}); ?>");
        $this->declaration(Malevich::casesDirective(), fn (string $expression) => "<?php {$recipe}->cases({$expression}); ?>");

        foreach ($this->axes() as $axis) {
            $this->declaration($axis, fn (string $expression) => "<?php {$recipe}->axis('{$axis}', {$expression}); ?>");
        }

        $this->registerDirective(Malevich::renderDirective(), function (string $expression) {
            $arguments = trim($expression) === '' ? '' : ", {$expression}";

            return '<?php echo \\'.Malevich::class."::ui(\$attributes, get_defined_vars(){$arguments}); ?>";
        });

        $this->registerDirective(Malevich::hasDirective(), function (string $expression) {
            $arguments = trim($expression) === '' ? '' : ", {$expression}";

            return '<?php if (\\'.Malevich::class."::has(\$attributes, get_defined_vars(){$arguments})): ?>";
        });

        // `@end` + the name, like @endauth: the closing tag is a plain endif.
        $this->registerDirective('end'.Malevich::hasDirective(), fn () => '<?php endif; ?>');

        // Blade ignores directives inside <x-...> tags, so @ui is rewritten there first.
        Blade::prepareStringsForCompilationUsing(ComponentTagCompiler::compile(...));
        // On plain tags a `class="..."` next to @ui would become a second class attribute.
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
        foreach (self::FIXED as $name) {
            if ($this->isBuiltIn($name) || $this->isTakenByAnotherPackage($name)) {
                throw new InvalidArgumentException("Malevich: the directive [@{$name}] is already taken by Blade or another package, and its name cannot be changed. Remove the clash or open an issue.");
            }
        }

        // name => the config option it comes from
        $names = [];

        foreach ([
            'render_directive' => Malevich::renderDirective(),
            'cases_directive' => Malevich::casesDirective(),
            'has_directive' => Malevich::hasDirective(),
        ] as $option => $name) {
            if (isset($names[$name])) {
                throw new InvalidArgumentException("Malevich: [@{$name}] is used both as {$names[$name]} and {$option}. Pick another name in config/malevich.php.");
            }

            $names[$name] = $option;
        }

        // The closing tag of the has-directive is a name of its own.
        $names['end'.Malevich::hasDirective()] = 'has_directive';

        foreach ($this->axes() as $name) {
            if (isset($names[$name]) && $names[$name] !== 'directives') {
                throw new InvalidArgumentException("Malevich: [@{$name}] is used both as {$names[$name]} and in directives. Pick another name in config/malevich.php.");
            }

            $names[$name] = 'directives';
        }

        foreach ($names as $name => $option) {
            $problem = match (true) {
                ! preg_match('/^[A-Za-z_]\w*$/', $name) => 'is not a valid directive name',
                in_array($name, self::FIXED, true) => 'is already a Malevich directive',
                $this->isBuiltIn($name) => 'is a built-in Blade directive',
                $this->isTakenByAnotherPackage($name) => 'is already registered as a Blade directive by your application or another package',
                $option === 'directives' && method_exists(ComponentAttributeBag::class, $name) => 'is already a method of $attributes',
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
