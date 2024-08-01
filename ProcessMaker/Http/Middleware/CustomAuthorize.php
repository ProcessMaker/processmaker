<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Middleware\Authorize as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $modelsString = implode('-', $models);
        $permission = $ability . '-' . $modelsString;

        try {
            return parent::handle($request, $next, $ability, ...$models);
        } catch (AuthorizationException $e) {
            return $this->handleCustomLogic($request, $next, $permission, $e, ...$models);
        } catch (AuthenticationException $e) {
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
            // Check for 'create-projects' permission and validate project access
            if ($this->hasPermission($userPermissions, 'create-projects')) {
                $projects = $this->getProjectsForUser($user->id);
                if ($projects && $this->isAllowedEndpoint($projects, $request->path(), $permission, $models)) {
                    return $next($request);
                }
            }
            // Re-throw the original exception if permission is not allowed
            throw $error;
        }

        return $next($request);
    }

    private function getUserPermissions($user)
    {
        return Cache::remember("user_{$user->id}_permissions", 86400, function () use ($user) {
            return $user->permissions->pluck('name')->toArray();
        });
    }

    private function hasPermission($userPermissions, $permission)
    {
        return in_array($permission, $userPermissions);
    }

    private function getProjectsForUser($userId)
    {
        // TODO: Could we assign projects to the cache too?
        // TODO: Cache user projects and project assets / invalidate cache when user is addded to projects and an asset added/removed from the project
        // TODO: Ensure that the cache does not get to big
        if (!hasPackage('package-projects')) {
            return [];
        }

        return Cache::remember("user_{$userId}_projects_with_assets", 86400, function () use ($userId) {
            return $this->getUserProjectsWithAssets($userId);
        });

        // dd($userProjectsWithAssets);

        // return $ownerProjects->merge($memberProjects)->unique()->values()->toArray();

        // $userProjects = Cache::remember("user_{$userId}_projects", 86400, function () {
        //     $ownerProjects = DB::table('projects')->where('user_id', $userId)->pluck('id');
        //     $memberProjects = DB::table('projects')
        //         ->join('project_members', 'projects.id', '=', 'project_members.project_id')
        //         ->where([
        //             'project_members.member_id' => $userId,
        //             'project_members.member_type' => User::class,
        //         ])->pluck('projects.id');

        //     return $ownerProjects->merge($memberProjects)->unique()->values()->toArray();
        // });
    }

    private function getUserProjectsWithAssets($userId)
    {
        $ownerProjectsWithAssets = DB::table('projects')
        ->join('project_assets', 'projects.id', '=', 'project_assets.project_id')
        ->where('projects.user_id', $userId)
        ->select('projects.id as project_id', 'project_assets.id as asset_id', 'project_assets.asset_type')
        ->get();

        $memberProjectsWithAssets = DB::table('projects')
            ->join('project_members', 'projects.id', '=', 'project_members.project_id')
            ->join('project_assets', 'projects.id', '=', 'project_assets.project_id')
            ->where([
                'project_members.member_id' => $userId,
                'project_members.member_type' => User::class,
            ])
            ->select('projects.id as project_id', 'project_assets.id as asset_id', 'project_assets.asset_type')
            ->get();

        $userProjectsWithAssets = $ownerProjectsWithAssets->merge($memberProjectsWithAssets)->unique()->values()->toArray();

        // Init the formatted array
        $userProjectWithAssetsArray = [];
        // Iterate over the results and format the array
        foreach ($userProjectsWithAssets as $project) {
            $projectId = $project->project_id;
            $assetType = $project->asset_type;
            $assetId = $project->asset_id;

            // Init project entry if not exists
            if (!isset($userProjectWithAssetsArray[$projectId])) {
                $userProjectWithAssetsArray[$projectId] = [];
            }

            // Init asset type entry if not exists
            if (!isset($userProjectWithAssetsArray[$projectId][$assetType])) {
                $userProjectWithAssetsArray[$projectId][$assetType] = [];
            }

            // Add asset ID to the respective asset type array
            $userProjectWithAssetsArray[$projectId][$assetType][] = $assetId;
        }

        return $userProjectWithAssetsArray;
    }

    private function isAllowedEndpoint($projectIds, $currentPath, $permission, $models)
    {
        $allowedEndpoints = $this->getAllowedEndpoints($projectIds);
        if (Str::contains($currentPath, $allowedEndpoints) && $this->isProjectAsset($permission, $models)) {
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
