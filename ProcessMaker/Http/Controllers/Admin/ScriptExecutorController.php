<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Services\ScriptMicroserviceService;

class ScriptExecutorController extends Controller
{
    public function index(Request $request, ScriptMicroserviceService $service)
    {
        if (!config('app.custom_executors')) {
            abort(404);
        }

        $scriptMicroserviceEnabled = config('script-runner-microservice.enabled');

        return view('admin.script-executors.index',
            [
                'script_microservice_enabled' => $scriptMicroserviceEnabled,
                'script_microservice_tenant_id' => $scriptMicroserviceEnabled
                    ? $service->getInstanceUuid()
                    : null,
            ]);
    }
}
