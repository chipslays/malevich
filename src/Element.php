<?php

declare(strict_types=1);

namespace Malevich;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
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
 * (your @props), an attribute on the component tag, then active presets.
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
        $classes = new ClassList;

        foreach ($this->recipe()->baseFor($this->target) as $base) {
            $classes = $classes->add($base);
        }

        foreach ($this->recipe()->axesFor($this->target) as $axis => $map) {
            $classes = $classes->add($map['*'] ?? null);

            $choice = $this->choice($axis);

            if ($choice !== null && $choice !== '*') {
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
        $own = $this->ownAttributes()?->getAttributes() ?? [];
        $consumed = [...$this->recipe()->axisNames(), 'class'];

        if ($this->recipe()->hasPresets()) {
            $consumed[] = 'preset';
        }

        $attributes = $own + $this->escapedExtra();
        $attributes = array_diff_key($attributes, array_flip($consumed));

        $classes = $this->toClasses();

        return new ComponentAttributeBag(
            $classes === '' ? $attributes : ['class' => $classes, ...$attributes],
        );
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
        $candidates = [
            fn () => $this->choices[$axis] ?? null,
            fn () => $this->scope[$axis] ?? null,
            fn () => $this->attributes->get($axis),
        ];

        foreach ($candidates as $candidate) {
            if (($value = $this->normalize($candidate())) !== null) {
                return $value;
            }
        }

        $value = null;

        foreach ($this->activePresets() as $preset) {
            $value = $this->normalize($this->recipe()->presetFor($preset, $this->target)[$axis] ?? null) ?? $value;
        }

        return $value;
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
        $mutate($clone);

        return $clone;
    }
}
