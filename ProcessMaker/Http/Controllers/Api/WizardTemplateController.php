<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\WizardTemplate;

class WizardTemplateController extends Controller
{
    /**
     * Get list of wizard templates
     */
    public function index(Request $request): ApiCollection
    {
        $perPage = $request->input('per_page', 10);
        $column = $request->input('order_by', 'id');
        $filter = $request->input('filter', '');

        $direction = $request->input('order_direction', 'asc');

        $query = WizardTemplate::with('process', 'process_template')->filter($filter)
            ->orderBy($column, $direction);

        $data = $query->paginate($perPage);

        return new ApiCollection($data);
    }
}
