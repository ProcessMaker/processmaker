<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use ProcessMaker\Http\Controllers\Process\ModelerController;

class CheckRouteType
{
    public function handle($request, Closure $next)
    {
        if ($request->has('request')) {
            // Route with 'inflight' parameter
            return app(ModelerController::class)->inflight($request);
        } else {
            // Route without 'inflight' parameter
            return app(ModelerController::class)->show($request);
        }
    }
}
