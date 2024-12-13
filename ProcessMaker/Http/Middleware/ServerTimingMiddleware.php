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
        // Start time for controller execution
        $startController = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate execution times
        $controllerTime = (microtime(true) - $startController) * 1000; // Convert to ms
        // Fetch service provider boot time
        $serviceProviderTime = ProcessMakerServiceProvider::getBootTime() ?? 0;
        // Fetch query time
        $queryTime = ProcessMakerServiceProvider::getQueryTime() ?? 0;

        $serverTiming = [
            "provider;dur={$serviceProviderTime}",
            "controller;dur={$controllerTime}",
            "db;dur={$queryTime}",
        ];

        $packageTimes = ProcessMakerServiceProvider::getPackageBootTiming();

        foreach ($packageTimes as $package => $timing) {
            $time = ($timing['end'] - $timing['start']) * 1000;

            // Only include packages that took more than 5ms
            if ($time > 5) {
                $serverTiming[] = "{$package};dur={$time}";
            }
        }

        // Add Server-Timing headers
        $response->headers->set('Server-Timing', $serverTiming);

        return $response;
    }
}
