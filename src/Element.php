<?php

declare(strict_types=1);

namespace Malevich;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Malevich\Exceptions\UnknownValueException;
use Malevich\Support\ClassList;
use Stringable;
use UnitEnum;

/**
 * Fluent, immutable resolver that turns a component's Recipe into the
 * final attributes of one of its elements (the root, or a named target).
 *
 * Usually created by the `@ui` directive:
 *
 *     <button @ui>...</button>
 *     <svg @ui('icon')>...</svg>
 *
 * or explicitly, for full control, from the attribute bag:
 *
 *     {{ $attributes->for('icon')->color('danger')->merge(['aria-hidden' => 'true']) }}
 *
 * The value of every axis is resolved in this order, first non-null wins:
 * an explicit call (`->color('danger')`), a variable in the template scope
 * (your @props), an attribute on the component tag, active presets, then
 * the recipe's @defaults.
 *
 * Unknown method calls are forwarded to a ComponentAttributeBag with the
 * resolved class already applied, so `->only()`, `->has()`, `->get()` and
 * friends keep working.
 *
 * @mixin ComponentAttributeBag
 */
final class Element implements Htmlable, Stringable
{
    private string $target;

    /**
     * @var array<string, mixed>
     */
    private array $choices = [];

    /**
     * @var list<string>
     */
    private array $presets = [];

    private ?ComponentAttributeBag $slotAttributes = null;

    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    /**
     * Memoized results. Reset whenever with() derives a new element.
     *
     * @var array<string, string|null>
     */
    private array $resolvedChoices = [];

    private ?string $resolvedClasses = null;

    private ?ComponentAttributeBag $resolvedBag = null;

    /**
     * @param  ComponentAttributeBag  $attributes  The bag the recipe was declared against.
     * @param  array<string, mixed>  $scope  Template variables to read axis values from.
     * @param  Recipe|null  $recipe  Defaults to the recipe declared against $attributes.
     */
    public function __construct(
        private readonly ComponentAttributeBag $attributes,
        ?string $target = null,
        private readonly array $scope = [],
        private readonly ?Recipe $recipe = null,
    ) {
        $this->target = $target ?? Malevich::defaultTarget();
    }

    /**
     * Resolve classes for another target of the component.
     */
    public function for(string $target): self
    {
        return $this->with(fn (self $s) => $s->target = $target);
    }

    /**
     * Merge the attributes of a named slot (`<x-slot name="icon" class="...">`)
     * into this element.
     *
     * @param  ComponentAttributeBag|ComponentSlot|mixed  $slot
     */
    public function slot(mixed $slot): self
    {
        $bag = match (true) {
            $slot instanceof ComponentAttributeBag => $slot,
            $slot instanceof ComponentSlot => $slot->attributes,
            default => null,
        };

        return $this->with(fn (self $s) => $s->slotAttributes = $bag);
    }

    /**
     * Set several axis values at once, or switch target and set them:
     *
     *     ->use(['size' => 'lg', 'color' => ['icon' => 'red']])
     *     ->use('icon', ['size' => 'lg'])
     *
     * @param  array<string, mixed>|string  $choicesOrTarget
     * @param  array<string, mixed>  $choices
     */
    public function use(array|string $choicesOrTarget = [], array $choices = []): self
    {
        return $this->with(function (self $s) use ($choicesOrTarget, $choices) {
            if (is_string($choicesOrTarget)) {
                $s->target = $choicesOrTarget;
            } else {
                $choices = array_merge($choicesOrTarget, $choices);
            }

            $s->choices = array_merge($s->choices, $choices);
        });
    }

    /**
     * Set the value of a single axis. An array value is treated as a map
     * keyed by target.
     */
    public function directive(string $axis, mixed $value): self
    {
        return $this->with(fn (self $s) => $s->choices[$axis] = $value);
    }

    /**
     * Apply a preset registered with @preset. Presets stack: a later one
     * overrides an earlier one, explicit values override both.
     */
    public function preset(?string $name): self
    {
        return $name === null ? $this : $this->with(fn (self $s) => $s->presets[] = $name);
    }

    /**
     * Default attributes for this element. Like ComponentAttributeBag::merge(),
     * attributes passed to the component win and classes are appended.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function merge(array $attributes = []): self
    {
        return $this->with(fn (self $s) => $s->extra = array_merge($s->extra, $attributes));
    }

    /**
     * `->color('red')` is shorthand for `->directive('color', 'red')`.
     * Anything that is not an axis is forwarded to the resolved attribute bag.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->isAxis($name)) {
            return $this->directive($name, $arguments[0] ?? null);
        }

        return $this->toAttributeBag()->{$name}(...$arguments);
    }

    /**
     * The resolved, de-duplicated class string, without the `class=""` wrapper.
     */
    public function toClasses(): string
    {
        return $this->resolvedClasses ??= $this->resolveClasses();
    }

    private function resolveClasses(): string
    {
        $classes = new ClassList;

        foreach ($this->recipe()->baseFor($this->target) as $base) {
            $classes = $classes->add($base);
        }

        foreach ($this->recipe()->axesFor($this->target) as $axis => $map) {
            $classes = $classes->add($map['*'] ?? null);

            $choice = $this->choice($axis);

            if ($choice !== null && $choice !== '*') {
                if (! array_key_exists($choice, $map) && Malevich::isStrict()) {
                    $this->ensureKnown($axis, $choice, $map);
                }

                $classes = $classes->add($map[$choice] ?? null);
            }
        }

        foreach ($this->recipe()->compoundsFor($this->target) as [$conditions, $compoundClasses]) {
            if ($this->matches($conditions)) {
                $classes = $classes->add($compoundClasses);
            }
        }

        $classes = $classes
            ->add($this->escapedExtra()['class'] ?? null)
            ->add($this->ownAttributes()?->get('class'));

        return Malevich::mergeClasses((string) $classes);
    }

    public function toAttributeBag(): ComponentAttributeBag
    {
        return $this->resolvedBag ??= $this->resolveAttributeBag();
    }

    private function resolveAttributeBag(): ComponentAttributeBag
    {
        $own = $this->ownAttributes()?->getAttributes() ?? [];
        $consumed = [...$this->recipe()->axisNames(), 'class'];

        if ($this->recipe()->hasPresets()) {
            $consumed[] = 'preset';
        }

        $attributes = $own + $this->escapedExtra();
        $attributes = array_diff_key($attributes, array_flip($consumed)) + $this->dataAttributes();

        // The bag is printed without escaping. Classes from the tag are already
        // escaped, so only a quote (from a class map or the class merger) could
        // still break out of the attribute - and turning it into &quot; is
        // harmless for values that were escaped before.
        $classes = str_replace('"', '&quot;', $this->toClasses());

        return new ComponentAttributeBag(
            $classes === '' ? $attributes : ['class' => $classes, ...$attributes],
        );
    }

    /**
     * `data-variant="primary"` & co. for the main element, when enabled in
     * config. A true flag is written without a value, a false one is left out,
     * so Tailwind's `data-loading:` matches exactly when the flag is on.
     *
     * @return array<string, string>
     */
    private function dataAttributes(): array
    {
        if ($this->target !== Malevich::defaultTarget() || ! Malevich::dataAttributes()) {
            return [];
        }

        $attributes = [];

        foreach ($this->recipe()->axisNames() as $axis) {
            $value = $this->choice($axis);

            if ($value === null || $value === 'false') {
                continue;
            }

            // Values from the tag are escaped already, so only a quote is turned
            // into &quot; - like the class, and never escaped twice.
            $attributes['data-'.str_replace('_', '-', Str::kebab($axis))] = $value === 'true' ? '' : str_replace('"', '&quot;', $value);
        }

        return $attributes;
    }

    /**
     * `merge:` values come from PHP, not from a Blade tag, so they are escaped
     * here - exactly like ComponentAttributeBag::merge() does.
     *
     * @return array<string, mixed>
     */
    private function escapedExtra(): array
    {
        return array_map(fn (mixed $value) => is_string($value) ? e($value) : $value, $this->extra);
    }

    public function toHtml(): string
    {
        return $this->toAttributeBag()->toHtml();
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    private function choice(string $axis): ?string
    {
        if (! array_key_exists($axis, $this->resolvedChoices)) {
            $this->resolvedChoices[$axis] = $this->resolveChoice($axis);
        }

        return $this->resolvedChoices[$axis];
    }

    private function resolveChoice(string $axis): ?string
    {
        $value = $this->normalize($this->choices[$axis] ?? null)
            ?? $this->normalize($this->scope[$axis] ?? null)
            ?? $this->normalize($this->attributes->get($axis));

        if ($value !== null) {
            return $value;
        }

        foreach ($this->activePresets() as $preset) {
            $value = $this->normalize($this->recipe()->presetFor($preset, $this->target)[$axis] ?? null) ?? $value;
        }

        return $value ?? $this->normalize($this->recipe()->defaultsFor($this->target)[$axis] ?? null);
    }

    /**
     * Strict mode: a value no class map or @compound of the component knows
     * is a typo. A value that only styles some of the elements is not, and
     * neither is the missing side of a boolean (`['true' => '...']` + false).
     *
     * @param  array<array-key, mixed>  $map
     */
    private function ensureKnown(string $axis, string $value, array $map): void
    {
        if (array_diff(array_keys($map), ['*']) === []) {
            return;
        }

        $known = array_values(array_unique(array_map(
            fn (mixed $known) => (string) $this->normalize($known),
            $this->recipe()->valuesOf($axis),
        )));

        $boolean = ['true', 'false'];

        if (in_array($value, $known, true) || (in_array($value, $boolean, true) && array_intersect($known, $boolean) !== [])) {
            return;
        }

        throw UnknownValueException::make($axis, $value, $this->target, $known);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matches(array $conditions): bool
    {
        foreach ($conditions as $axis => $expected) {
            $expected = array_map($this->normalize(...), (array) $expected);

            if (! in_array($this->choice($axis), $expected, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function activePresets(): array
    {
        $fromComponent = $this->normalize($this->scope['preset'] ?? $this->attributes->get('preset'));

        return array_values(array_filter([$fromComponent, ...$this->presets]));
    }

    /**
     * Turn any supported value (per-target map, enum, bool, scalar) into
     * the key looked up in a class map.
     */
    private function normalize(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[$this->target] ?? null;
        }

        return match (true) {
            $value === null, is_array($value) => null,
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof UnitEnum => $value->name,
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value), $value instanceof Stringable => (string) $value,
            default => null,
        };
    }

    /**
     * The bag whose plain attributes belong to this element: the slot's,
     * if one was given, otherwise the component's own - but only on the
     * root target, so `class="..."` on `<x-button>` never leaks into its icon.
     */
    private function ownAttributes(): ?ComponentAttributeBag
    {
        return $this->slotAttributes ?? ($this->target === Malevich::defaultTarget() ? $this->attributes : null);
    }

    private function isAxis(string $name): bool
    {
        return $this->recipe()->hasAxis($name) || in_array($name, Malevich::directives(), true);
    }

    private function recipe(): Recipe
    {
        return $this->recipe ?? Malevich::recipe($this->attributes);
    }

    /**
     * @param  callable(self): mixed  $mutate
     */
    private function with(callable $mutate): self
    {
        $clone = clone $this;
        $clone->resolvedChoices = [];
        $clone->resolvedClasses = null;
        $clone->resolvedBag = null;
        $mutate($clone);

        return $clone;
    }
}
