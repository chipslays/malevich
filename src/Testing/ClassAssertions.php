<?php

declare(strict_types=1);

namespace Malevich\Testing;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Testing\Assert;
use Illuminate\Testing\TestComponent;
use Illuminate\Testing\TestResponse;
use Illuminate\Testing\TestView;
use Pest\Expectation;
use Stringable;

/**
 * Assert that rendered HTML has an element with a set of classes, in any
 * order and among any other classes:
 *
 *     $this->blade('<x-button variant="danger" />')->assertSeeClasses('bg-red-600 text-white');
 *     $this->get('/')->assertDontSeeClasses('bg-red-600');
 *     expect($html)->toHaveClasses(['bg-red-600', 'text-white']);
 */
final class ClassAssertions
{
    /**
     * Add the assertions to Laravel's test views, components and responses,
     * and to Pest's expect() when Pest is installed.
     */
    public static function register(): void
    {
        foreach ([TestView::class, TestComponent::class, TestResponse::class] as $class) {
            if ($class::hasMacro('assertSeeClasses')) {
                continue;
            }

            /** @param string|array<array-key, string> $classes */
            $class::macro('assertSeeClasses', function (string|array $classes) {
                ClassAssertions::assertSee(ClassAssertions::html($this), $classes);

                return $this;
            });

            /** @param string|array<array-key, string> $classes */
            $class::macro('assertDontSeeClasses', function (string|array $classes) {
                ClassAssertions::assertDontSee(ClassAssertions::html($this), $classes);

                return $this;
            });
        }

        if (class_exists(Expectation::class) && ! Expectation::hasExtend('toHaveClasses')) {
            /** @param string|array<array-key, string> $classes */
            (new Expectation(null))->extend('toHaveClasses', function (string|array $classes) {
                ClassAssertions::assertSee(ClassAssertions::html($this->value), $classes);

                return $this;
            });
        }
    }

    /**
     * @param  string|array<array-key, string>  $classes
     */
    public static function assertSee(string $html, string|array $classes): void
    {
        $classes = self::split($classes);

        Assert::assertTrue(
            self::has($html, $classes),
            'Failed asserting that an element has the classes ['.implode(' ', $classes).'].'.self::found($html),
        );
    }

    /**
     * @param  string|array<array-key, string>  $classes
     */
    public static function assertDontSee(string $html, string|array $classes): void
    {
        $classes = self::split($classes);

        Assert::assertFalse(
            self::has($html, $classes),
            'Failed asserting that no element has the classes ['.implode(' ', $classes).'].'.self::found($html),
        );
    }

    /**
     * Whether a single element carries every one of the classes.
     *
     * @param  list<string>  $classes
     */
    public static function has(string $html, array $classes): bool
    {
        foreach (self::classAttributes($html) as $attribute) {
            if (array_diff($classes, $attribute) === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * The classes of every element in the HTML, one list per element.
     *
     * @return list<list<string>>
     */
    public static function classAttributes(string $html): array
    {
        preg_match_all('/\sclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $matches, PREG_SET_ORDER);

        return array_map(
            fn (array $match) => self::split(html_entity_decode(($match[2] ?? '') !== '' ? $match[2] : ($match[1] ?? ''), ENT_QUOTES | ENT_HTML5)),
            $matches,
        );
    }

    public static function html(mixed $value): string
    {
        return match (true) {
            $value instanceof TestResponse => (string) $value->getContent(),
            $value instanceof Htmlable => $value->toHtml(),
            is_string($value), $value instanceof Stringable => (string) $value,
            default => '',
        };
    }

    /**
     * @param  string|array<array-key, string>  $classes
     * @return list<string>
     */
    private static function split(string|array $classes): array
    {
        $classes = is_array($classes) ? implode(' ', $classes) : $classes;

        return preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private static function found(string $html): string
    {
        $attributes = array_map(fn (array $classes) => '  class="'.implode(' ', $classes).'"', self::classAttributes($html));

        return $attributes === [] ? ' The HTML has no class attributes.' : "\nClass attributes in the HTML:\n".implode("\n", array_slice($attributes, 0, 20));
    }
}
