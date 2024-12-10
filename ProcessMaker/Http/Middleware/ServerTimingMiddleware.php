<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        // Process the request
        $response = $next($request);

        // Calculate elapsed time
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        // Add Server-Timing header
        $response->headers->set('Server-Timing', "controller;dur={$duration}");

        return $response;
    }
}
