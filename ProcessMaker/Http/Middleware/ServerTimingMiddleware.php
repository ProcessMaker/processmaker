<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use ProcessMaker\Services\WorkerBootTimingService;
use Symfony\Component\HttpFoundation\Response;

class ServerTimingMiddleware
{
    public function __construct(private WorkerBootTimingService $workerBootTimingService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.server_timing.enabled')) {
            return $next($request);
        }

        ProcessMakerServiceProvider::beginRequestTiming();

        // Start time for controller execution
        $startController = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate execution times
        $controllerTime = (microtime(true) - $startController) * 1000; // Convert to ms
        // Fetch service provider boot time
        $serviceProviderTime = $this->workerBootTimingService->getProviderBootTime() ?? 0;
        // Fetch query time
        $queryTime = ProcessMakerServiceProvider::getQueryTime() ?? 0;

        $serverTiming = [
            "provider;dur={$serviceProviderTime}",
            "controller;dur={$controllerTime}",
            "db;dur={$queryTime}",
        ];

        $hasLaravelStart = defined('LARAVEL_START');
        if ($hasLaravelStart) {
            $bootTiming = ($startController - \LARAVEL_START) * 1000; // Convert to ms
            array_unshift($serverTiming, "boot;dur={$bootTiming}");
        }

        $packageTimes = $this->workerBootTimingService->getPackageBootTiming();
        $minPackageTime = config('app.server_timing.min_package_time');

        foreach ($packageTimes as $package => $timing) {
            $time = ($timing['end'] - $timing['start']) * 1000;

            // Only include packages that took more than MIN_PACKAGE_TIME ms
            if ($time > $minPackageTime) {
                $serverTiming[] = "{$package};dur={$time}";
            }
        }

        // Add Server-Timing headers
        $response->headers->set('Server-Timing', $serverTiming);

        return $response;
    }
}
