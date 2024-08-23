<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Process;
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

        $query = WizardTemplate::with('process')->filter($filter)
            ->orderBy($column, $direction);

        $data = $query->paginate($perPage);

        return new ApiCollection($data);
    }

    public function getHelperProcess($wizardTemplateUuid)
    {
        $helperProcessID = WizardTemplate::select('helper_process_id')->where('uuid', $wizardTemplateUuid)->value('helper_process_id');
        $start_events = Process::select('start_events')->where('id', $helperProcessID)->value('start_events');

        return json_encode([
            'helper_process_id' => $helperProcessID,
            'start_events' => json_encode($start_events),
        ]);
    }
}
