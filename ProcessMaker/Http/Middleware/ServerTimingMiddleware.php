<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class ServerTimingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start time for the entire request
        $startEndpoint = microtime(true);
        // Track total query execution time
        $queryTime = 0;

        // Listen to query events and accumulate query execution time
        DB::listen(function ($query) use (&$queryTime) {
            $queryTime += $query->time; // Query time in milliseconds
        });

        // Start time for controller execution
        $startController = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate execution times
        $controllerTime = (microtime(true) - $startController) * 1000; // Convert to ms
        $endpointTime = (microtime(true) - $startEndpoint) * 1000; // Convert to ms

        // Fetch service provider boot time
        $serviceProviderTime = ProcessMakerServiceProvider::getBootTime() ?? 0;

        // Add Server-Timing headers
        $response->headers->set('Server-Timing', [
            "providers;dur={$serviceProviderTime}",
            "endpoint;dur={$endpointTime}",
            "controller;dur={$controllerTime}",
            "db;dur={$queryTime}",
        ]);

        return $response;
    }
}
