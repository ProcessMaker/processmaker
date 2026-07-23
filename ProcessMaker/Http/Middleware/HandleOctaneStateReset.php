<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Providers\ProcessMakerServiceProvider;

class HandleOctaneStateReset
{
    /**
     * Handle an incoming request.
     *
     * Resets per-request state that Octane would otherwise persist
     * across requests in long-running workers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Reset query timing metrics for this request
        ProcessMakerServiceProvider::beginRequestTiming();

        return $next($request);
    }
}