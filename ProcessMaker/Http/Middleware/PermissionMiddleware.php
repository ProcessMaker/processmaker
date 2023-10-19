<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Exception\PermissionDeniedException;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        try {
            if (!$request->user()) {
                throw new PermissionDeniedException('User not authenticated');
            }

            $userPermissions = array_map(function ($e) {
                return $e['name'];
            }, $request->user()->permissions->toArray());

            $userPermissionsIntersect = array_intersect($userPermissions, $permissions);

            if (empty($userPermissionsIntersect)) {
                throw new PermissionDeniedException('Insufficient permissions');
            }

            return $next($request);
        } catch (PermissionDeniedException $e) {
            \Log::error('Permission Error: ' . $e->getMessage());

            // Handle the exception here as needed
            // You might return a custom error response or rethrow the exception.
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
