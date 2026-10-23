<?php

declare(strict_types=1);

namespace Malevich;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use InvalidArgumentException;
use Malevich\Exceptions\UnknownValueException;
use Malevich\Support\ClassList;
use Malevich\Support\ScopedProps;
use Malevich\Support\TargetAttribute;
use Stringable;
use UnitEnum;

/**
 * Fluent, immutable resolver that turns a component's Recipe into the
 * final attributes of one of its elements (the root, or a named target).
 *
 * Usually created by the `@attributes` directive:
 *
 *     <button @attributes>...</button>
 *     <svg @attributes('icon')>...</svg>
 *
 * or explicitly, for full control, from the attribute bag:
 *
 *     {{ $attributes->for('icon')->color('danger')->merge(['aria-hidden' => 'true']) }}
 *
 * The value of every option is resolved in this order, first non-null wins:
 * an explicit call (`->color('danger')`), a named-slot attribute, a
 * target:option attribute, a variable in the template scope (your @props,
 * with their defaults), then an attribute on the component tag.
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

    private readonly MalevichManager $malevich;

    /**
     * @var array<string, mixed>
     */
    private array $choices = [];

    private ?ComponentAttributeBag $slotAttributes = null;

    private string $ownClasses = '';

    /**
     * @var array<string, mixed>
     */
    private array $extra = [];

    /**
     * @var array<string, mixed>
     */
    private array $override = [];

    /**
     * Memoized results. Reset whenever with() derives a new element.
     *
     * @var array<string, string|null>
     */
    private array $resolvedChoices = [];

    private ?string $resolvedClasses = null;

    private ?ComponentAttributeBag $resolvedBag = null;

    /**
     * @var array<string, array{value: mixed, modifiers: array<string, string|bool|null>}>|null
     */
    private ?array $resolvedTargetAttributes = null;

    /**
     * @param  ComponentAttributeBag  $attributes  The bag the recipe was declared against.
     * @param  array<string, mixed>  $scope  Template variables to read option values from.
     * @param  Recipe|null  $recipe  Defaults to the recipe declared against $attributes.
     * @param  MalevichManager|null  $malevich  Defaults to the application's.
     */
    public function __construct(
        private readonly ComponentAttributeBag $attributes,
        ?string $target = null,
        private readonly array $scope = [],
        private readonly ?Recipe $recipe = null,
        ?MalevichManager $malevich = null,
    ) {
        $this->malevich = $malevich ?? app(MalevichManager::class);
        $this->target = $target ?? $this->malevich->defaultTarget();
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
     * Set several option values at once, or switch target and set them:
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
     * Set the value of a single option. An array value is treated as a map
     * keyed by target.
     */
    public function option(string $option, mixed $value): self
    {
        return $this->with(fn (self $s) => $s->choices[$option] = $value);
    }

    /**
     * Modifiers of a target:attribute, e.g. icon:trailing.rounded.size[24].
     *
     * @return array<string, string|bool|null>
     */
    public function modifiers(string $attribute): array
    {
        return $this->targetAttributes()[$attribute]['modifiers'] ?? [];
    }

    /**
     * The element's own classes: the `class="..."` written next to
     * `@attributes` inside the component. They work like `@base`: they come
     * first, and the option maps go on top of them.
     */
    public function classes(string $classes): self
    {
        return $this->with(fn (self $s) => $s->ownClasses = trim($s->ownClasses.' '.$classes));
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
     * Attributes the component insists on: unlike merge(), they win over the
     * ones passed to the component. A null value leaves the attribute as the
     * caller set it, false removes it. Classes can't be overridden - they
     * always add up, so use the class maps for them. In a template:
     * `@attributes(override: ['aria-busy' => $loading ? 'true' : null])`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function override(array $attributes = []): self
    {
        if (array_key_exists('class', $attributes)) {
            throw new InvalidArgumentException('override: cannot replace classes; declare them in a class map or pass them with merge:.');
        }

        return $this->with(fn (self $s) => $s->override = array_merge($s->override, $attributes));
    }

    /**
     * `->color('red')` is shorthand for `->option('color', 'red')`.
     * Anything that is not an option is forwarded to the resolved attribute bag.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->isOption($name)) {
            return $this->option($name, $arguments[0] ?? null);
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

    /**
     * The full class string of this element for every value of one option,
     * resolved on the server, so Alpine can switch between them:
     *
     *     <div x-data="{ variant: 'primary', classes: {{ Js::from($attributes->classesFor('variant')) }} }">
     *         <button x-bind:class="classes[variant]">
     *
     * Everything else stays as it is on this element (other options, the
     * target, `class` from the tag). Without $values, every value the
     * component knows for the option is used.
     *
     * @param  array<array-key, mixed>|null  $values  Only the values are used, keys are ignored.
     * @return array<string, string> [value] => classes
     */
    public function classesFor(string $option, ?array $values = null): array
    {
        $classes = [];

        foreach ($values ?? $this->recipe()->valuesOf($option) as $value) {
            $key = $this->normalize($value);

            if ($key !== null) {
                $classes[$key] = $this->option($option, $value)->toClasses();
            }
        }

        return $classes;
    }

    private function resolveClasses(): string
    {
        $classes = new ClassList;

        foreach ($this->fragments() as [, $fragment]) {
            $classes = $classes->add($fragment);
        }

        return $this->malevich->mergeClasses((string) $classes);
    }

    /**
     * Every piece of the class string, in order, with where it comes from:
     * `@base`, the element's own `class=""`, each option's map, the matching `@compound`s, `merge:` and
     * the `class` of the tag or slot.
     *
     * @return list<array{0: string, 1: mixed}> [label, classes]
     */
    private function fragments(): array
    {
        $fragments = [];

        foreach ($this->recipe()->baseFor($this->target) as $base) {
            $fragments[] = ['@'.$this->malevich->directive('base'), $base];
        }

        if ($this->ownClasses !== '') {
            $fragments[] = ['class=""', $this->ownClasses];
        }

        foreach ($this->recipe()->optionsFor($this->target) as $option => $map) {
            if (isset($map['*'])) {
                $fragments[] = ["{$option} = *", $map['*']];
            }

            $choice = $this->choice($option);

            if ($choice !== null && $choice !== '*') {
                if (! array_key_exists($choice, $map) && $this->malevich->isStrict()) {
                    $this->ensureKnown($option, $choice, $map);
                }

                $fragments[] = ["{$option} = {$choice}", $map[$choice] ?? null];
            }
        }

        foreach ($this->recipe()->compoundsFor($this->target) as [$conditions, $compoundClasses]) {
            if ($this->matches($conditions)) {
                $fragments[] = ['@'.$this->malevich->directive('compound').'('.self::describeConditions($conditions).')', $compoundClasses];
            }
        }

        $fragments[] = ['merge:', $this->escapedExtra()['class'] ?? null];
        $fragments[] = ['class from the tag', $this->ownAttributes()?->get('class')];

        return $fragments;
    }

    /**
     * The debug comment for this element: empty unless debug mode is on.
     *
     *     <!--
     *          ┌─ ⬛ <x-button variant="outline" size="md">
     *          │
     *          ├─ 🔹 variant = outline ← from tag
     *          ├─ 🔹 size = md ← from props
     *          │
     *          ├─ 🔸 @base → inline-flex rounded-lg
     *          ├─ 🔸 variant = outline → border bg-white
     *          ├─ 🔸 size = md → h-10 px-4
     *          │
     *          └─ ✅ inline-flex rounded-lg border bg-white h-10 px-4
     *     -->
     */
    public function debugComment(): string
    {
        if (! $this->malevich->isDebugging()) {
            return '';
        }

        $component = $this->malevich->componentName($this->scope) ?? 'component';
        $element = $this->target === $this->malevich->defaultTarget() ? '' : " › {$this->target}";

        $props = '';

        foreach (array_unique([...$this->recipe()->optionNames(), ...$this->debugOptionNames()]) as $option) {
            $props .= match ($choice = $this->choice($option)) {
                null, 'false' => '',
                'true' => " {$option}",
                default => " {$option}=\"{$choice}\"",
            };
        }

        $lines = ["┌─ ⬛ <x-{$component}{$props}>{$element}", '│'];

        foreach ($this->debugOptionNames() as $option) {
            $choice = $this->choice($option);
            $lines[] = $choice === null
                ? "├─ 🔹 {$option}: not set"
                : "├─ 🔹 {$option} = {$choice} ← ".$this->choiceSource($option);
        }

        if (count($lines) > 2) {
            $lines[] = '│';
        }

        foreach ($this->fragments() as [$label, $fragment]) {
            $classes = Arr::toCssClasses($fragment);

            if ($classes !== '') {
                $lines[] = "├─ 🔸 {$label} → {$classes}";
            } elseif (str_contains($label, '=') && ! str_ends_with($label, '= *')) {
                $lines[] = "├─ ⚠️ {$label} → no classes for this value";
            }
        }

        $before = (string) array_reduce($this->fragments(), fn (ClassList $list, array $fragment) => $list->add($fragment[1]), new ClassList);
        $result = $this->toClasses();
        $removed = array_diff(explode(' ', $before), explode(' ', $result));

        if ($removed !== []) {
            $lines[] = '├─ 🧹 tailwind-merge removed → '.implode(' ', $removed);
        }

        if (end($lines) !== '│') {
            $lines[] = '│';
        }

        $lines[] = '└─ ✅ '.($result === '' ? 'no classes' : $result);

        // A comment can't contain "--"; tag values are already escaped.
        $body = str_replace('--', '- -', implode("\n     ", $lines));

        return "<!--\n     {$body}\n-->\n";
    }

    /**
     * Options that matter for this element: those with a class map here and
     * those its matching or non-matching @compound conditions test.
     *
     * @return list<string>
     */
    private function debugOptionNames(): array
    {
        $names = array_keys($this->recipe()->optionsFor($this->target));

        foreach ($this->recipe()->compoundsFor($this->target) as [$conditions]) {
            $names = [...$names, ...array_keys($conditions)];
        }

        return array_values(array_unique(array_map(strval(...), $names)));
    }

    private function choiceSource(string $option): string
    {
        return match (true) {
            $this->normalize($this->choices[$option] ?? null) !== null => '->'.$option.'()',
            $this->slotChoice($option) !== null => 'from slot',
            $this->normalize($this->targetAttributes()[$option]['value'] ?? null) !== null => 'from '.$this->target.':'.$option,
            // The data Blade passed to the template: what the tag set, before @props defaults.
            $this->normalize($this->scope[$option] ?? null) !== null => is_array($this->scope['__data'] ?? null) && array_key_exists($option, $this->scope['__data'])
                ? 'from tag'
                : 'from props',
            $this->normalize($this->attributes->get($option)) !== null => 'from tag',
            default => 'not set',
        };
    }

    /**
     * @param  array<array-key, mixed>  $conditions
     */
    private static function describeConditions(array $conditions): string
    {
        return implode(', ', array_map(
            fn (mixed $expected, int|string $option) => $option.'='.implode('|', array_map(
                fn (mixed $value) => is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) ? (string) $value : '?'),
                (array) $expected,
            )),
            $conditions,
            array_keys($conditions),
        ));
    }

    public function toAttributeBag(): ComponentAttributeBag
    {
        return $this->resolvedBag ??= $this->resolveAttributeBag();
    }

    private function resolveAttributeBag(): ComponentAttributeBag
    {
        $own = $this->ownAttributes()?->getAttributes() ?? [];
        $options = $this->recipe()->optionNames();
        $consumed = [...$options, ...array_map(Str::kebab(...), $options), 'class'];

        // Only targets declared by this component own a namespace.
        // Every other namespaced attribute stays on the root unchanged.
        $targets = $this->recipe()->targets();

        foreach (array_keys($own) as $name) {
            $parsed = TargetAttribute::parse((string) $name);

            if ($parsed !== null && in_array($parsed['target'], $targets, true)) {
                unset($own[$name]);
            }
        }

        $attributes = $own + $this->escapedExtra();
        $attributes = array_diff_key($attributes, array_flip($consumed)) + $this->dataAttributes();

        // override: values come from PHP too, so they are escaped like merge:.
        foreach ($this->override as $name => $value) {
            if ($value !== null) {
                $attributes[$name] = is_string($value) ? e($value) : $value;
            }
        }

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
        if ($this->target !== $this->malevich->defaultTarget() || ! $this->malevich->dataAttributes()) {
            return [];
        }

        $attributes = [];

        if (($component = $this->malevich->componentName($this->scope)) !== null) {
            $attributes['data-component'] = $component;
        }

        foreach ($this->recipe()->optionNames() as $option) {
            $value = $this->choice($option);

            if ($value === null || $value === 'false') {
                continue;
            }

            // Values from the tag are escaped already, so only a quote is turned
            // into &quot; - like the class, and never escaped twice.
            $attributes['data-'.str_replace('_', '-', Str::kebab($option))] = $value === 'true' ? '' : str_replace('"', '&quot;', $value);
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

    /**
     * The attributes as printed inside a plain tag: with a leading space,
     * or nothing at all when there is nothing to print.
     */
    public function inTag(): string
    {
        $html = $this->toHtml();

        return $html === '' ? '' : ' '.$html;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    private function choice(string $option): ?string
    {
        if (! array_key_exists($option, $this->resolvedChoices)) {
            $this->resolvedChoices[$option] = $this->resolveChoice($option);
        }

        return $this->resolvedChoices[$option];
    }

    private function resolveChoice(string $option): ?string
    {
        return $this->normalize($this->choices[$option] ?? null)
            ?? $this->slotChoice($option)
            ?? $this->normalize($this->targetAttributes()[$option]['value'] ?? null)
            ?? $this->normalize($this->scope[$option] ?? null)
            ?? $this->normalize($this->attributes->get($option));
    }

    private function slotChoice(string $option): ?string
    {
        return $this->normalize($this->slotAttributes?->get($option))
            ?? $this->normalize($this->slotAttributes?->get(Str::kebab($option)));
    }

    /**
     * Strict mode: a value no class map or @compound of the component knows
     * is a typo. A value that only styles some of the elements is not, and
     * neither is the missing side of a boolean (`['true' => '...']` + false).
     *
     * @param  array<array-key, mixed>  $map
     */
    private function ensureKnown(string $option, string $value, array $map): void
    {
        if (array_diff(array_keys($map), ['*']) === []) {
            return;
        }

        $known = array_values(array_unique(array_map(
            fn (mixed $known) => (string) $this->normalize($known),
            $this->recipe()->valuesOf($option),
        )));

        $boolean = ['true', 'false'];

        if (in_array($value, $known, true) || (in_array($value, $boolean, true) && array_intersect($known, $boolean) !== [])) {
            return;
        }

        throw UnknownValueException::make($option, $value, $this->target === $this->malevich->defaultTarget() ? null : $this->target, $known);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function matches(array $conditions): bool
    {
        foreach ($conditions as $option => $expected) {
            $expected = array_map($this->normalize(...), (array) $expected);

            if (! in_array($this->choice($option), $expected, true)) {
                return false;
            }
        }

        return true;
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
        $own = $this->slotAttributes ?? ($this->target === $this->malevich->defaultTarget() ? $this->attributes : null);
        $targeted = array_map(fn (array $attribute) => $attribute['value'], $this->targetAttributes());

        return $own === null && $targeted === [] ? null : new ComponentAttributeBag(($own?->getAttributes() ?? []) + $targeted);
    }

    /**
     * @return array<string, array{value: mixed, modifiers: array<string, string|bool|null>}>
     */
    private function targetAttributes(): array
    {
        if ($this->resolvedTargetAttributes !== null) {
            return $this->resolvedTargetAttributes;
        }

        $attributes = [];

        $scoped = $this->scope[ScopedProps::SCOPE_VARIABLE] ?? ScopedProps::attributes($this->attributes);

        foreach (array_replace($scoped, $this->attributes->getAttributes()) as $name => $value) {
            $parsed = TargetAttribute::parse((string) $name);

            if ($parsed !== null && $parsed['target'] === $this->target) {
                $attributes[$parsed['name']] = ['value' => $value, 'modifiers' => $parsed['modifiers']];
            }
        }

        return $this->resolvedTargetAttributes = $attributes;
    }

    private function isOption(string $name): bool
    {
        return $this->recipe()->hasOption($name) || in_array($name, $this->malevich->options(), true);
    }

    private function recipe(): Recipe
    {
        return $this->recipe ?? $this->malevich->recipe($this->attributes);
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
        $clone->resolvedTargetAttributes = null;
        $mutate($clone);

        return $clone;
    }
}
