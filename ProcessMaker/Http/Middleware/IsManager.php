<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IsManager
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return abort(401, 'Unauthenticated');
        }

        // if user is administrator, allow access
        if ($user->is_administrator) {
            return $next($request);
        }

        if (!Cache::get("user_{$user->id}_manager")) {
            // if user is not manager, continue
            return $next($request);
        }

        // get the required permissions for this specific URL
        $requiredPermissions = $this->getRequiredPermissionsForRequest($request);

        if (empty($requiredPermissions)) {
            // if no required permissions, continue
            return $next($request);
        }

        // simulate that the user has all the necessary permissions for this request
        $this->simulateRequiredPermissionsForRequest($user, $requiredPermissions);

        try {
            // process the request - the internal endpoints will handle the permission validation
            $response = $next($request);

            // clean up the simulated permissions after processing the request
            $this->cleanupSimulatedPermission($user);

            return $response;
        } catch (\Exception $e) {
            // make sure to clean up the simulated permissions even if there is an exception
            $this->cleanupSimulatedPermission($user);
            throw $e;
        }
    }

    /**
     * Simula que el usuario tiene los permisos requeridos solo para esta solicitud
     */
    private function simulateRequiredPermissionsForRequest($user, array $requiredPermissions)
    {
        try {
            // get the current permissions of the user
            $currentPermissions = $user->loadPermissions();

            // filter only the permissions that the user does not have
            $permissionsToAdd = array_diff($requiredPermissions, $currentPermissions);

            if (empty($permissionsToAdd)) {
                return;
            }

            // simulate the permissions by adding them temporarily to the cache of permissions
            $cacheKey = "user_{$user->id}_permissions";
            $simulatedPermissions = array_merge($currentPermissions, $permissionsToAdd);

            // save in cache temporarily (only for this request)
            // use a very short time to expire quickly if not cleaned manually
            Cache::put($cacheKey, $simulatedPermissions, 5); // 5 segundos como fallback
        } catch (\Exception $e) {
            Log::error('IsManager middleware - Error simulating permissions: ' . $e->getMessage());
        }
    }

    /**
     * clean up the simulated permissions from the cache after processing the request
     */
    private function cleanupSimulatedPermission($user)
    {
        try {
            $cacheKey = "user_{$user->id}_permissions";

            // delete the cache to force the reload of real permissions
            Cache::forget($cacheKey);
        } catch (\Exception $e) {
            Log::error('IsManager middleware - Error cleaning up simulated permissions: ' . $e->getMessage());
        }
    }

    /**
     * get the required permissions for the current URL
     */
    private function getRequiredPermissionsForRequest(Request $request): array
    {
        $permissions = [];

        try {
            $url = $request->fullUrl();
            $path = $request->path();
            $method = $request->method();

            // first, get permissions from middlewares of the route
            $middlewarePermissions = $this->getPermissionsFromMiddlewares($request);
            $permissions = array_merge($permissions, $middlewarePermissions);

            // then, get permissions based on URL patterns
            $urlPermissions = $this->getPermissionsFromUrlPatterns($url, $path, $method);
            $permissions = array_merge($permissions, $urlPermissions);
        } catch (\Exception $e) {
            Log::error('IsManager middleware - Error getting required permissions: ' . $e->getMessage());
        }

        return array_unique($permissions);
    }

    /**
     * get permissions from the middlewares of the route
     */
    private function getPermissionsFromMiddlewares(Request $request): array
    {
        $permissions = [];

        try {
            // get all the middlewares of the route
            $middlewares = $request->route()->middleware();

            // filter only the middlewares that contain 'can:'
            $permissionMiddlewares = array_filter($middlewares, function ($middleware) {
                return str_contains($middleware, 'can:');
            });

            // extract the permissions from each middleware
            foreach ($permissionMiddlewares as $middleware) {
                // format: "can:permission" or "can:permission,model"
                if (preg_match('/can:([^,]+)/', $middleware, $matches)) {
                    $permissions[] = $matches[1];
                }
            }
        } catch (\Exception $e) {
            Log::error('IsManager middleware - Error getting permissions from middlewares: ' . $e->getMessage());
        }

        return $permissions;
    }

    /**
     * get permissions based on URL patterns
     */
    private function getPermissionsFromUrlPatterns(string $url, string $path, string $method): array
    {
        $permissions = [];

        // for now we only support GET methods
        if ($method !== 'GET') {
            return $permissions;
        }

        try {
            // URL patterns and their corresponding permissions
            $urlPatterns = [
                // patterns for users
                '/api\/.*\/users(\?.*)?$/' => 'view-users',

                // patterns for saved searches
                '/api\/.*\/saved-searches\/columns(\?.*)?$/' => 'view-saved-searches-columns',
            ];

            // check each pattern
            foreach ($urlPatterns as $pattern => $permission) {
                if (preg_match($pattern, $url)) {
                    $permissions[] = $permission;
                }
            }
        } catch (\Exception $e) {
            Log::error('IsManager middleware - Error getting permissions from URL patterns: ' . $e->getMessage());
        }

        return $permissions;
    }
}
