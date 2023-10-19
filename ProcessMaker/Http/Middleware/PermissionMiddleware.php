<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
                throw new AuthorizationException(__('Not authorized to complete this request.'));
            }

            $userPermissions = array_map(function ($e) {
                return $e['name'];
            }, $request->user()->permissions->toArray());

            $userPermissionsIntersect = array_intersect($userPermissions, $permissions);

            if (empty($userPermissionsIntersect)) {
                throw new AuthorizationException(__('Not authorized to complete this request.'));
            }

            return $next($request);
        } catch (AuthorizationException $e) {
            \Log::error('Permission Error: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
