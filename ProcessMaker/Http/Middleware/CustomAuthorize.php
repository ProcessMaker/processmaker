<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authorize as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthorize extends Middleware
{
    public function handle($request, Closure $next, $ability, ...$models)
    {
        try {
            $response = parent::handle($request, $next, $ability, ...$models);

            if ($response->getStatusCode() == 200) {
                return $response;
            }
        } catch (\Exception $e) {
            // TODO: dump $e to find the exact class that is thrown and catch it
            return $this->handleCustomLogic($request, $next, $ability, ...$models);
        }

        // TODO: DOUBLE CHECK IF THIS IS NECESSARY MAY ONLY NEED THE EXCEPTION HANDLER
        return $this->handleCustomLogic($request, $next, $ability, ...$models);
    }

    private function handleCustomLogic($request, Closure $next, $ability, ...$models)
    {
        $user = $request->user();
        $userPermissions = $this->getUserPermissions($user);

        if (!$this->hasPermission($userPermissions, $ability)) {
            if ($this->hasPermission($userPermissions, 'create-projects')) {
                $projects = $this->getProjectsForUser($user->id);
                if ($projects && $this->isAllowedEndpoint($projects, $request->path(), $ability, $models)) {
                    return $next($request);
                }
            }
            // TODO: instead of abort throw the same exception thrown in above try catch
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }

    private function getUserPermissions($user)
    {
        return Cache::remember("user_{$user->id}_permissions", 86400, function () use ($user) {
            return $user->permissions->pluck('name')->toArray();
        });
    }

    private function hasPermission($userPermissions, $ability)
    {
        return in_array($ability, $userPermissions);
    }

    private function getProjectsForUser($userId)
    {
        // TODO: Could we assign projects to the cache too?
        // TODO: Cache user projects and project assets / invalidate cache when user is addded to projects and an asset added/removed from the project
        // TODO: Ensure that the cache does not get to big
        if (!hasPackage('package-projects')) {
            return [];
        }

        $ownerProjects = DB::table('projects')->where('user_id', $userId)->pluck('id');
        $memberProjects = DB::table('projects')
            ->join('project_members', 'projects.id', '=', 'project_members.project_id')
            ->where([
                'project_members.member_id' => $userId,
                'project_members.member_type' => User::class,
            ])->pluck('projects.id');

        return $ownerProjects->merge($memberProjects)->unique()->values()->toArray();
    }

    private function isAllowedEndpoint($projectIds, $currentPath, $ability, $models)
    {
        $allowedEndpoints = $this->getAllowedEndpoints($projectIds);

        if (Str::contains($currentPath, $allowedEndpoints) && $this->isProjectAsset($ability, $models)) {
            return true;
        }

        return false;
    }

    private function getAllowedEndpoints($projectIds)
    {
        $allowedEndpoints = ['api'];
        // TODO: Project assets will be stored in the projects cache for the user
        $projectAssets = DB::table('project_assets')
            ->select('asset_id', 'asset_type')
            ->whereIn('project_id', $projectIds)
            ->distinct()
            ->get();

        foreach ($projectAssets as $asset) {
            $allowedEndpoints =
                array_merge($allowedEndpoints, $this->getEndpointsForAsset($asset->asset_type, $asset->asset_id));
        }

        return $allowedEndpoints;
    }

    private function getEndpointsForAsset($assetType, $assetId)
    {
        $endpoints = [];

        switch ($assetType) {
            case Process::class:
                $endpoints[] = "modeler/{$assetId}";
                break;
            case Screen::class:
                $endpoints[] = "designer/screen-builder/{$assetId}/edit";
                $endpoints[] = "designer/screens/{$assetId}/edit";
                $endpoints[] = 'designer/screens/preview';
                break;
            case Script::class:
                $endpoints[] = "designer/scripts/{$assetId}/builder";
                $endpoints[] = "designer/scripts/{$assetId}/edit";
                $endpoints[] = 'designer/scripts/preview';
                break;
            default:
                if (class_exists('ProcessMaker\Packages\Connectors\DataSources\Models\DataSource')
                    && $assetType === 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource') {
                    $endpoints[] = "designer/data-sources/{$assetId}/edit";
                }
                if (class_exists('ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable')
                    && $assetType === 'ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable') {
                    $endpoints[] = "decision-tables/table-builder/{$assetId}/edit";
                }
                break;
        }

        return $endpoints;
    }

    private function isProjectAsset($permission, $params)
    {
        return $params && $params[0]
            ? $this->handleUpdateDeleteOperations($permission, class_basename($params[0]))
            : $this->checkForListCreateOperations($permission);
    }

    private function handleUpdateDeleteOperations($permission, $modelClass)
    {
        return $this->checkPermissionForAsset($permission, $this->getAssetName($modelClass));
    }

    private function getAssetName($modelClass)
    {
        $asset = Str::snake(class_basename($modelClass));

        if ($modelClass === 'DataSource') {
            $asset = 'data-source';
        }

        return $asset;
    }

    private function checkForListCreateOperations($permission)
    {
        $projectAssetTypes = ['process', 'screen', 'script', 'data-source', 'decision_table', 'pm-block'];

        foreach ($projectAssetTypes as $asset) {
            if (Str::contains($permission, $asset)) {
                return true;
            }
        }

        return false;
    }

    private function checkPermissionForAsset($permission, $asset)
    {
        return Str::contains($permission, $asset);
    }
}
