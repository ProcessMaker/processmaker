<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $start = microtime(true);
        $queryTime = 0;

        // Listen to query events and accumulate query execution time
        DB::listen(function ($query) use (&$queryTime) {
            $queryTime += $query->time; // Query time in milliseconds
        });

        // Process the request
        $response = $next($request);

        // Calculate elapsed time
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        // Add Server-Timing header
        $response->headers->set('Server-Timing', "controller;dur={$duration}");
         $response->headers->set('Server-Timing', [
            "controller;dur={$duration}",
            "db;dur={$queryTime}"
        ]);

        return $response;
    }
}
