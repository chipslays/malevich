<?php

declare(strict_types=1);

namespace Malevich\Catalog;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET  /malevich?component=button   the page
 * POST /malevich/preview            the playground (JSON)
 *
 * @internal
 */
final class CatalogController
{
    public function __invoke(Request $request): View
    {
        abort_unless(Catalog::enabled(), 404);

        $catalog = Catalog::make();
        $components = $catalog->components();
        $name = $request->query('component');
        $name = is_string($name) && isset($components[$name]) ? $name : array_key_first($components);
        $sheet = $name === null ? null : $catalog->sheet($name, $components[$name]);

        return view('malevich::catalog', [
            'components' => $components,
            'name' => $name,
            'component' => $name === null ? null : $components[$name],
            'sheet' => $sheet,
            'slots' => $sheet === null ? [] : $catalog->slotsOf($sheet['slot']),
            'slotClasses' => $sheet === null ? [] : $catalog->slotClassesOf($sheet['slot']),
            'props' => $name === null ? [] : $catalog->props($components[$name]),
            'snippets' => $catalog->snippets($components),
            'bladeAllowed' => Catalog::bladeAllowed(),
            'head' => $catalog->head(),
            'path' => trim((string) config('malevich.catalog.path', 'malevich'), '/'),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        abort_unless(Catalog::enabled(), 404);

        $catalog = Catalog::make();
        $components = $catalog->components();
        $name = $request->input('component');

        abort_unless(is_string($name) && isset($components[$name]), 404);

        $string = fn (string $key): string => is_string($value = $request->input($key)) ? $value : '';
        $array = fn (string $key): array => is_array($value = $request->input($key)) ? $value : [];

        $cell = $catalog->preview($name, $components[$name], $array('values'), [
            'mode' => $string('mode'),
            'slots' => $array('slots'),
            'slotClasses' => $array('slotClasses'),
            'attributes' => $array('attributes'),
            'class' => $string('class'),
            'blade' => $string('blade'),
        ]);

        return new JsonResponse(['tag' => $cell->tag, 'html' => $cell->html, 'error' => $cell->error]);
    }
}
