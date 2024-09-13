<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Middleware\Authorize as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\ProjectAssetTrait;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthorize extends Middleware
{
    use ProjectAssetTrait;

    public function handle($request, Closure $next, $ability, ...$models)
    {
        $modelsString = implode('-', $models);
        // Set the permission based on whether $modelsString is empty or not
        $permission = $modelsString ? $ability . '-' . $modelsString : $ability;

        try {
            return parent::handle($request, $next, $ability, ...$models);
        } catch (AuthorizationException $e) {
            return $this->handleCustomLogic($request, $next, $permission, $e, ...$models);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred in CustomAuthorize middleware.', [
                'exception' => $e,
                'permission' => $permission,
                'models' => $models,
            ]);

            return $this->handleCustomLogic($request, $next, $permission, $e, ...$models);
        }
    }

    private function handleCustomLogic($request, Closure $next, $permission, $error, ...$models)
    {
        $user = $request->user();
        $userPermissions = $this->getUserPermissions($user);
        if (!$this->hasPermission($userPermissions, $permission)) {
            if ($this->hasPermission($userPermissions, 'create-projects')) {
                // Handle middleware-based logic if no models are provided (indexes)
                if (empty($models) && $this->passesMiddlewareCheck($request) ||
                    !empty($models) && $this->userHasAccessToProject($request, $user->id, ...$models)) {
                    return $next($request);
                }
            }
            // Re-throw the original exception if permission is not allowed
            throw $error;
        }

        return $next($request);
    }

    private function passesMiddlewareCheck($request)
    {
        $projectAssets = ['process', 'screen', 'script', 'flow_genie', 'decision_table', 'data-source'];
        $middlewares = array_filter($request->route()->middleware(), function ($m) {
            return str_contains($m, 'can:');
        });

        $middleware = array_shift($middlewares);

        return Str::contains($middleware, $projectAssets);
    }

    private function userHasAccessToProject($request, $userId, $models)
    {
        $projectAssets = self::getProjectAssetsForUser($userId);

        // Extract the first model from the route parameters
        $model = $request->route()->parameter($models);

        if ($model) {
            $modelClass = get_class($model);
            $modelId = $model->id;

            return isset($projectAssets[$modelClass]) && in_array($modelId, $projectAssets[$modelClass]);
        }

        return false;
    }

    private function getUserPermissions($user)
    {
        return Cache::remember("user_{$user->id}_permissions", 86400, function () use ($user) {
            return $user->permissions()->pluck('name')->toArray();
        });
    }

    private function hasPermission($userPermissions, $permission)
    {
        return in_array($permission, $userPermissions);
    }
}
