# Debugging

Two switches help when classes don't come out as expected: **debug mode** explains every element, **strict mode** stops on typos.

## Debug mode

"Why does this button have these classes?" Turn on debug mode, and every element tells you in an HTML comment right in front of it.

```dotenv
# .env
MALEVICH_DEBUG=true
```

Open the page source or DevTools:

```html
<!--
     ┌─ 🎨 <x-button variant="outline" size="md">
     │
     ├─ 🔹 variant = outline ← from tag
     ├─ 🔹 size = md ← from props
     │
     ├─ 🔸 @base → inline-flex items-center rounded-lg font-medium
     ├─ 🔸 variant = outline → border border-zinc-300 bg-white
     ├─ 🔸 size = md → h-10 px-4
     ├─ 🔸 @compound(variant=outline, size=md) → shadow-sm
     ├─ 🔸 class from the tag → mt-2
     │
     └─ ✅ inline-flex items-center rounded-lg font-medium border border-zinc-300 bg-white h-10 px-4 shadow-sm mt-2
-->
<button class="inline-flex items-center ...">
```

How to read it:

| Line | Meaning |
|---|---|
| `┌─ 🎨 <x-button ...>` | The component and the value of each option. |
| `🔹 size = md ← from props` | Where the value came from: the tag, a `@props` default, or an explicit `->size()` call. |
| `🔸 ... → classes` | Which declaration added which classes, in the order they are built. |
| `⚠️ size = xl → no classes for this value` | A value that matches nothing - often a typo. |
| `🧹 tailwind-merge removed → px-4` | What [tailwind-merge](07-tailwind.md#class-conflicts) dropped. |
| `└─ ✅ ...` | The final classes. |

Inner elements get their own comment: `<x-button ...> › icon`.

You can also switch it in code; a call wins over `.env`:

```php
// app/Providers/AppServiceProvider.php
use Malevich\Malevich;

public function boot(): void
{
    Malevich::debug(app()->isLocal());
}
```

> [!WARNING]
> Never turn it on in production: it makes the HTML larger and shows how your components are built.

## Strict mode

By default a value with no matching key renders nothing - silently. A typo like `variant="primay"` just leaves the button unstyled. Strict mode turns that into an error, so you notice right away:

```dotenv
# .env
MALEVICH_STRICT=true
```

or in code, like Eloquent's `Model::shouldBeStrict()` (a call wins over `.env`):

```php
public function boot(): void
{
    Malevich::strict(! app()->isProduction());
}
```

Now an unknown value throws `Malevich\Exceptions\UnknownValueException`:

```text
Malevich: variant [primay] is not defined; expected one of: primary, outline, ghost.
```

It checks the value wherever it came from, so a typo in a `@props` default is caught too. It stays quiet when nothing is wrong:

- the value is `null` (not set);
- the element has no class map for that option;
- the value is used elsewhere in the component - in another element's map or in a `@compound` condition;
- it's the missing side of a boolean: a `['true' => '...']` map with `false`.

Keep it off in production: there an unstyled element is better than an error page.

> [!TIP]
> Strict mode only sees pages you open. [`malevich:lint`](11-testing.md#catching-typos-before-the-page-is-rendered) checks every view in one go.

## Still stuck?

See [FAQ and troubleshooting](faq.md) - the most common problems and their fixes.

---

Next: [Testing and CI](11-testing.md)
