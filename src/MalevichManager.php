<?php

declare(strict_types=1);

namespace Malevich;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Illuminate\View\FileViewFinder;
use LogicException;
use Malevich\Support\ComponentScanner;
use WeakMap;

/**
 * The runtime behind the Blade directives and the `Malevich` facade.
 * Bound as a singleton: one per application, so Octane workers and tests
 * get a fresh one with the application.
 */
class MalevichManager
{
    /**
     * Template variable that holds the component's Recipe.
     */
    public const SCOPE_VARIABLE = '__malevichRecipe';

    /**
     * Template variable that holds the element `@attributes` resolved for a component tag.
     */
    public const ATTRIBUTES_VARIABLE = '__malevichAttributes';

    /**
     * Malevich's own directives by role, with their default names. Every
     * name can be changed in config/malevich.php (`directives`).
     */
    public const DIRECTIVES = [
        'attributes' => 'attributes',
        'base' => 'base',
        'option' => 'option',
        'compound' => 'compound',
        'cases' => 'cases',
        'styled' => 'styled',
    ];

    /**
     * @var (Closure(string): string)|null
     */
    private ?Closure $classMerger = null;

    /**
     * Set from code (a service provider); null falls back to the config.
     */
    private ?bool $strict = null;

    private ?bool $debug = null;

    /**
     * Config values read on every render, resolved once.
     *
     * @var array{default_target: string, directives: array<string, string>, options: list<string>, data_attributes: bool, strict: bool, debug: bool, merger: (Closure(string): string)|null}|null
     */
    private ?array $config = null;

    /**
     * Recipes by the attribute bag of the component instance that declared
     * them. A WeakMap, so a recipe lives exactly as long as its component:
     * nothing leaks between renders, requests or Octane workers.
     *
     * @var WeakMap<ComponentAttributeBag, Recipe>
     */
    private WeakMap $recipes;

    /**
     * Component names by compiled view path.
     *
     * @var array<string, string|null>
     */
    private array $componentNames = [];

    public function __construct(private readonly Repository $repository)
    {
        $this->recipes = new WeakMap;
    }

    /**
     * Post-process every resolved class string, e.g. to resolve Tailwind
     * conflicts in a way of your own:
     *
     *     Malevich::mergeClassesUsing(fn (string $classes) => MyMerger::merge($classes));
     *
     * Pass null to restore the default: tailwind-merge when installed and
     * `merge_classes` is "auto", de-duplication only otherwise.
     *
     * @param  (Closure(string): string)|null  $merger
     */
    public function mergeClassesUsing(?Closure $merger): void
    {
        $this->classMerger = $merger;
    }

    public function mergeClasses(string $classes): string
    {
        $merger = $this->classMerger ?? $this->config()['merger'];

        return $merger ? $merger($classes) : $classes;
    }

    /**
     * Throw instead of silently rendering no classes when an option gets a
     * value its class maps don't define (`size="xl"`). Meant for local
     * development and tests, in a service provider's boot():
     *
     *     Malevich::strict(! app()->isProduction());
     *
     * A call here wins over the `strict` config option.
     */
    public function strict(bool $strict = true): void
    {
        $this->strict = $strict;
    }

    public function isStrict(): bool
    {
        return $this->strict ?? $this->config()['strict'];
    }

    /**
     * Print an HTML comment in front of every element that explains its
     * classes: the component, the value of each option and where it came
     * from, and which declaration added which classes. For development:
     *
     *     Malevich::debug(app()->isLocal());
     *
     * A call here wins over the `debug` config option.
     */
    public function debug(bool $debug = true): void
    {
        $this->debug = $debug;
    }

    public function isDebugging(): bool
    {
        return $this->debug ?? $this->config()['debug'];
    }

    /**
     * The recipe a component declares against its attribute bag.
     */
    public function recipe(?ComponentAttributeBag $attributes): Recipe
    {
        $attributes ??= $this->outsideComponent();

        return $this->recipes[$attributes] ??= new Recipe($this->defaultTarget());
    }

    /**
     * Runtime of the `@attributes` directive. Option values are picked up from the
     * template scope (your @props) and from the attribute bag, so the
     * template never has to repeat them.
     *
     * A named slot with the same name as the target (`@attributes('title')` and
     * `<x-slot:title>`) is picked up automatically. Pass `slot:` to use a
     * differently named slot, or `slot: false` to opt out.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     * @param  array<string, mixed>  $merge  Extra attributes, as with $attributes->merge().
     */
    public function attributes(
        ?ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
        string $class = '',
    ): Element {
        $attributes ??= $this->outsideComponent();

        if ($slot === null && $target !== null && ($scope[$target] ?? null) instanceof ComponentSlot) {
            $slot = $scope[$target];
        }

        $recipe = ($scope[self::SCOPE_VARIABLE] ?? null) instanceof Recipe ? $scope[self::SCOPE_VARIABLE] : null;

        return (new Element($attributes, $target, $scope, $recipe, $this))
            ->slot($slot)
            ->classes($class)
            ->merge($merge);
    }

    /**
     * Runtime of `@styled('target')`: whether the element ends up with any
     * classes. Lets a template skip a part that exists only in some variants.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     * @param  array<string, mixed>  $merge  Extra attributes, as with @attributes.
     */
    public function styled(
        ?ComponentAttributeBag $attributes,
        array $scope,
        ?string $target = null,
        mixed $slot = null,
        array $merge = [],
    ): bool {
        return $this->attributes($attributes, $scope, $target, $slot, $merge)->toClasses() !== '';
    }

    public function defaultTarget(): string
    {
        return $this->config()['default_target'];
    }

    /**
     * The configured name of one of Malevich's directives, by its role:
     * attributes, base, option, compound, cases or styled.
     */
    public function directive(string $role): string
    {
        return $this->config()['directives'][$role] ?? throw new LogicException("Malevich: there is no [{$role}] directive.");
    }

    /**
     * Every directive name by role, e.g. ['attributes' => 'attributes', 'base' => 'base', ...].
     *
     * @return array<string, string>
     */
    public function directives(): array
    {
        return $this->config()['directives'];
    }

    /**
     * Option names from config, each a directive and an $attributes method:
     * e.g. ['variant', 'size', 'color'].
     *
     * @return list<string>
     */
    public function options(): array
    {
        return $this->config()['options'];
    }

    /**
     * Whether the main element gets a `data-*` attribute per option.
     */
    public function dataAttributes(): bool
    {
        return $this->config()['data_attributes'];
    }

    /**
     * The Blade tag a component file is available as, e.g. "x-forms.input".
     */
    public function componentTag(string $name): string
    {
        $prefix = $this->repository->get('malevich.components.prefix');

        return 'x-'.($prefix ? $prefix.'::' : '').str_replace(['/', '\\'], '.', $name);
    }

    /**
     * The tag name of the component whose template the scope belongs to
     * (e.g. "button", "ui::badge"), or null when it can't be told. Read from
     * the source path Blade records at the end of every compiled view, so it
     * costs a file read once per view - only debug mode and data attributes
     * ask for it.
     *
     * @param  array<string, mixed>  $scope  The template's get_defined_vars().
     */
    public function componentName(array $scope): ?string
    {
        $compiled = $scope['__path'] ?? null;

        if (! is_string($compiled)) {
            return null;
        }

        if (! array_key_exists($compiled, $this->componentNames)) {
            $finder = app('view')->getFinder();
            $tail = is_file($compiled) ? (string) file_get_contents($compiled, offset: max(0, (int) filesize($compiled) - 2048)) : '';

            $this->componentNames[$compiled] = $finder instanceof FileViewFinder && preg_match('/\/\*\*PATH (.+?) ENDPATH\*\*\//', $tail, $match)
                ? (new ComponentScanner(new Filesystem, $finder))->nameOf($match[1])
                : null;
        }

        return $this->componentNames[$compiled];
    }

    /**
     * Forget the config values read so far, so changes made with config()
     * at runtime are picked up. The service provider calls it on boot.
     */
    public function flushConfig(): void
    {
        $this->config = null;
    }

    /**
     * Reset everything to the defaults. Mainly useful in tests.
     */
    public function flush(): void
    {
        $this->classMerger = null;
        $this->strict = null;
        $this->debug = null;
        $this->recipes = new WeakMap;
        $this->componentNames = [];
        $this->flushConfig();
    }

    /**
     * @return array{default_target: string, directives: array<string, string>, options: list<string>, data_attributes: bool, strict: bool, debug: bool, merger: (Closure(string): string)|null}
     */
    private function config(): array
    {
        return $this->config ??= [
            'default_target' => $this->string('malevich.default_target', 'default'),
            'directives' => array_combine(
                array_keys(self::DIRECTIVES),
                array_map(fn (string $role, string $default) => $this->string("malevich.directives.{$role}", $default), array_keys(self::DIRECTIVES), self::DIRECTIVES),
            ),
            'options' => array_values(array_filter((array) $this->repository->get('malevich.options', []), is_string(...))),
            'data_attributes' => (bool) $this->repository->get('malevich.data_attributes', false),
            'strict' => (bool) $this->repository->get('malevich.strict', false),
            'debug' => (bool) $this->repository->get('malevich.debug', false),
            'merger' => $this->repository->get('malevich.merge_classes', 'auto') === 'auto' ? $this->tailwindMerger() : null,
        ];
    }

    private function string(string $key, string $default): string
    {
        $value = $this->repository->get($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * tailwind-merge, if the application has it installed.
     *
     * @return (Closure(string): string)|null
     */
    private function tailwindMerger(): ?Closure
    {
        $facade = 'TailwindMerge\Laravel\Facades\TailwindMerge';

        if (class_exists($facade)) {
            return fn (string $classes): string => $facade::merge($classes);
        }

        $merger = 'TailwindMerge\TailwindMerge';

        if (class_exists($merger)) {
            return fn (string $classes): string => $merger::instance()->merge($classes);
        }

        return null;
    }

    private function outsideComponent(): never
    {
        throw new LogicException('Malevich: @'.$this->directive('attributes').' and the class map directives only work inside a Blade component, where $attributes is defined.');
    }
}
