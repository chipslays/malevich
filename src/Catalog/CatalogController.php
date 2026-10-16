<?php

declare(strict_types=1);

namespace Malevich\Catalog;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * GET /malevich?component=button
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

        return view('malevich::catalog', [
            'components' => $components,
            'name' => $name,
            'component' => $name === null ? null : $components[$name],
            'sheet' => $name === null ? null : $catalog->sheet($name, $components[$name]),
            'head' => $catalog->head(),
            'path' => trim((string) config('malevich.catalog.path', 'malevich'), '/'),
        ]);
    }
}
