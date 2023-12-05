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
        $direction = $request->input('order_direction', 'asc');

        $query = WizardTemplate::query()
            ->orderBy($column, $direction);

        $data = $query->paginate($perPage);

        return new ApiCollection($data);
    }
}
