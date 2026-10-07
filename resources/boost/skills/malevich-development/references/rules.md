# Rules (must follow) and anti-patterns

## Hard rules

1. Declare every directive **before** `@ui`, right after `@props`.
2. Always-on classes go in `@base`. Never put `class="..."` on the element that has `@ui`, and never combine `@ui` with `$attributes->class(...)`, `{{ $attributes }}` or `@class` on the same element.
3. Write full literal class names in maps. Never build them: `'bg-'.$color.'-500'`, `bg-{{ $color }}-500`. Tailwind cannot see them.
4. Every default in `@props` must be an existing key of that option's map (case-sensitive).
5. In components with `@preset`, options default to `null`.
6. Inside `<x-...>` tags use single quotes in `@ui(...)` arguments.
7. Primitive-based components: `as` is a prop forwarded with `:as="$as"`; do not declare `href`, `target`, `disabled`, `type` in `@props` unless forwarding them.
8. Inner elements: each `@ui('x')` needs matching target declarations if it should be styled; tag `class` never reaches it.
9. Match the project: option names, value names, size scale, palette, prefix (`components.prefix`), Tailwind version.
10. Keep public prop names and values stable when refactoring; do not change rendered classes unless asked.

## Anti-patterns

- `@ui` before the directives it depends on.
- Hardcoded `class` next to `@ui`.
- Dynamic class-name concatenation.
- Encoding a combination as a fake value (`'primary-lg'`) instead of `@compound`.
- Using `@preset` for one-off looks.
- Non-null defaults with presets (they beat the preset).
- Hardcoding `as="button"` on the primitive inside an overridable component.
- Declaring `href`/`disabled` in `@props` of a primitive-based component without forwarding.
- `@ui` in layouts/pages.
- Using `'*'` keys where `@base` is clearer.
- Registering a global directive for an option used in one component (use `@directive`).
- Custom directive names that collide with Blade, Malevich or `$attributes` methods.
- Assuming Tailwind conflicts resolve themselves: without tailwind-merge, `px-4` + user `px-8` both remain.
- Forgetting `php artisan view:clear` after changing `config/malevich.php` or upgrading the package.
- Pasting every directive of the scaffold into a component; keep only real options.

## Design principles

- One option = one concern: `variant` look, `color` hue, `size` dimensions. Cross-effects go to `@compound`.
- Components that sit side by side (button, input, select) share the same size keys and heights: use a shared partial.
- Accessibility defaults go to `merge:` so users can override: `role="alert"`, `role="switch"` + `aria-checked`, `type="button"`, `aria-hidden="true"` on decorative icons.
- Use `data-disabled:` with the primitive for disabled styling.
- Inner parts (icon, spinner, title, thumb) are targets with the same option names so one prop styles all of them.
- Interactive element that can be link/button/disabled -> primitive; plain container -> normal tag with `@ui`.
- Prefer slots for rich content; same-named slot lets users restyle that part.
- Add a usage comment at the top of each component: `{{-- <x-name variant="..."> --}}`.

## Boost project rules

Package-level skills cannot ship project rules; those belong to the application (`.ai/rules`, recorded via Boost's `record-rule` tool). If the user states an app-specific Malevich convention (e.g. "all components live in `ui/` with prefix `ui`", "our sizes are sm/md/lg/xl", "always install tailwind-merge"), suggest recording it as a rule with glob `resources/views/components/**` rather than editing this skill.
