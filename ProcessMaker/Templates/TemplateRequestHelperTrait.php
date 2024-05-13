<?php

namespace ProcessMaker\Templates;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Api\ExportController;

trait TemplateRequestHelperTrait
{
    /**
     * Get process template manifest.
     */
    public function getManifest(string $type, int $id): array
    {
        $response = (new ExportController)->manifest($type, $id);

        return json_decode($response->getContent(), true);
    }

    /**
     * Get sort parameters from request.
     */
    protected function getRequestSortBy(Request $request, string $default): array
    {
        $column = $request->input('order_by', $default);
        $direction = $request->input('order_direction', 'asc');

        return [$column, $direction];
    }

    /**
     * Get included relationships from request.
     */
    protected function getRequestInclude(Request $request) : array
    {
        $include = $request->input('include');

        return $include ? explode(',', $include) : [];
    }
}
