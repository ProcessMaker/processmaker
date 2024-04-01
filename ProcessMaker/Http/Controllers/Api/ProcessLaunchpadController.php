<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;

class ProcessLaunchpadController extends Controller
{
    public function index(Request $request, Process $process)
    {
        // Get the processes  active
        $processes = ProcessLaunchpad::where('process_id', $process->id)
            ->get()
            ->collect();

        return new ApiResource($processes);
    }

    public function store(Request $request, Process $process)
    {
        $launch = new ProcessLaunchpad();
        $properties = $request->input('launchpad_properties');
        try {
            $newLaunch = $launch->updateOrCreate([
                'process_id' => $process->id,
                'user_id' => Auth::user()->id,
                'launchpad_properties' => $properties,
            ]);
            $launch->newId = $newLaunch->id;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return new ApiResource($launch->refresh());
    }

    public function destroy(ProcessLaunchpad $launch)
    {
        // Delete launchpad
        $launch->delete();

        return response([], 204);
    }
}
