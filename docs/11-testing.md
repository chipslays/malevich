# Testing and CI

Malevich adds class assertions for your tests and a command that finds typos in all your views before anything is rendered.

## Testing a component's classes

`assertSee('class="..."')` breaks as soon as the class order changes. Use the class assertions instead. They pass when **one element** has **all** the classes, in any order:

```php
$this->blade('<x-button variant="danger">Delete</x-button>')
    ->assertSeeClasses('bg-red-600 text-white')
    ->assertDontSeeClasses('bg-zinc-900');

$this->get('/settings')->assertSeeClasses(['rounded-2xl', 'shadow-xl']);
```

They work on `$this->blade()`, `$this->component()` and HTTP responses.

With Pest there's an expectation too:

```php
use Illuminate\Support\Facades\Blade;

expect(Blade::render('<x-badge color="green">New</x-badge>'))
    ->toHaveClasses('bg-green-100 text-green-700');
```

A failure lists the class attributes it found, so a missing or misspelled class is easy to spot.

> [!TIP]
> Turn on [strict mode](10-debugging.md#strict-mode) in your tests (`MALEVICH_STRICT=true` in `phpunit.xml`), and a test fails as soon as a view uses a value a component doesn't know.

## Catching typos before the page is rendered

Strict mode catches `variant="primay"` when the page is rendered. `malevich:lint` catches it in **every** view, including pages nobody has opened:

```bash
php artisan malevich:lint
```

```text
  ✗ resources/views/settings.blade.php:14  <x-button>
    variant [primay] is not defined; expected one of: primary, outline, ghost. Did you mean [primary]?

   ERROR  1 problem in 212 tags of 9 components.
```

It reads your components without running them, collects the values every option accepts, and checks every `<x-...>` tag in your views.

```bash
php artisan malevich:lint resources/views/admin   # only some folders or files
```

### In CI

The command exits with code `1` when it finds a problem, so it can fail a build. With `--format=github` the problems appear right on the pull request:

```yaml
# .github/workflows/ci.yml
- run: php artisan malevich:lint --format=github
```

### What it can and can't check

It never guesses - what it can't read for sure, it skips:

- Literal values are checked: `variant="primary"`. Bound and echoed ones are not: `:variant="$v"`, `variant="{{ $v }}"`.
- A class map that isn't written out (`@variant($variants)`) turns off the check for that option. An `@option($name, ...)` or a conditional partial (`@includeIf`) turns it off for the whole component.
- A map that is a plain string (`@color('text-red-500')`) accepts any value.

So a clean run means "no typos in what can be checked" - and with written-out class maps that is nearly everything.

---

Next: [Artisan commands](12-commands.md)
