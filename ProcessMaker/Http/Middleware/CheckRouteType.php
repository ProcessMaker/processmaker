<?php

namespace ProcessMaker\Http\Middleware;

// use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Process\ModelerController;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class CheckRouteType
{
    public function handle(Request $request)
    {
        $uri = $request->getPathInfo();

        if (strpos($uri, '/inflight') !== false) {
            $manager = new ModelerManager();
            // // $process = Process::find($request->route('process'));
            $processRequest = ProcessRequest::find($request->route('request'));
            if (!$processRequest) {
                $processRequest = new ProcessRequest();
            }
            if ($request->route('request') !== null) {
                return app(ModelerController::class)->inflight($manager, $request->route('process'), $request->route('request'));
            } else {
                return app(ModelerController::class)->inflight($manager, $request->route('process'), $processRequest);
            }
        } else {
            return app(ModelerController::class)->show($request);
        }
    }
}
