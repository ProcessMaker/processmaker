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

        return view('admin.script-executors.index',
            [
                'script_microservice_enabled' => config('script-runner-microservice.enabled'),
                'script_microservice_instance_uuid' => $service->getInstanceUuid(),
            ]);
    }
}
