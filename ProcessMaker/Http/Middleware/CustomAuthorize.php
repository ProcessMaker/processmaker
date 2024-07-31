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
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $ability, ...$models)
    {
        try {
            // Call the parent handle method first
            $response = parent::handle($request, $next, $ability, ...$models);
        } catch (\AuthenticationException $e) {
            // Parent logic was not successful, run custom logic
            return $this->handleCustomLogic($request, $next, $ability, ...$models);
        } catch (\AuthorizationException $e) {
            // Parent logic was not successful, run custom logic
            return $this->handleCustomLogic($request, $next, $ability, ...$models);
        } catch (\Exception $e) {
            // Parent logic was not successful, run custom logic
            return $this->handleCustomLogic($request, $next, $ability, ...$models);
        }

        // Check if the response is successful before proceeding
        if ($response->getStatusCode() === 200) {
            // Parent logic was successful, proceed with request
            return $response;
        }

        // Parent logic was not successful, run custom logic
        return $this->handleCustomLogic($request, $next, $ability, ...$models);
    }

    private function handleCustomLogic($request, Closure $next, $ability, ...$models)
    {
        $user = $request->user();

        // Retrieve the permissions from cache
        $cacheKey = "user_{$user->id}_permissions";
        $userPermissions = Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->permissions->pluck('name')->toArray();
        });

        // Check if the user has the required ability
        if (!in_array($ability, $userPermissions)) {
            // Additional checks for 'create-projects' permission
            if (in_array('create-projects', $userPermissions)) {
                $projects = $this->getProjectsForUser($user->id);
                if (empty($projects)) {
                    abort(403, 'Unauthorized action.');
                }

                $isAllowedEndpoint = $this->checkAllowedEndpoints($projects, $request->path());

                if ($isAllowedEndpoint && $this->isProjectAsset($ability, $models)) {
                    return $next($request);
                }

                abort(403, 'Unauthorized action.');
            }
        }
    }

    /**
     * Retrieves the projects associated with the user.
     */
    private function getProjectsForUser(int $userId): array
    {
        if (!hasPackage('package-projects')) {
            return [];
        }

        // Get projects where the current user is an owner
        $ownerProjects = DB::table('projects')
            ->where('user_id', $userId)
            ->pluck('id');

        // Get projects where the current user is a member
        $memberProjects = DB::table('projects')
            ->join('project_members', 'projects.id', '=', 'project_members.project_id')
            ->where([
                'project_members.member_id' => $userId,
                'project_members.member_type' => User::class,
            ])
            ->pluck('projects.id');

        // Combine owner and member projects
        return $ownerProjects->merge($memberProjects)->unique()->values()->toArray();
    }

    /**
     * Checks if the current path is allowed based on the user's project assets.
     */
    private function checkAllowedEndpoints(array $projectIds, string $currentPath): bool
    {
        $allowedEndpoints = [
            'api',
        ];

        $dataSourceClass = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';
        $decisionTableClass = 'ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable';

        // Get the assets associated with the user's projects
        $projectAssets = DB::table('project_assets')
            ->select('asset_id', 'asset_type')
            ->whereIn('project_id', $projectIds)
            ->distinct()
            ->get();

        foreach ($projectAssets as $asset) {
            // Get each project asset's type and id
            $assetId = $asset->asset_id;
            $assetType = $asset->asset_type;

            // Check asset types and push to $allowedEndpoints
            if ($assetType === Process::class) {
                $allowedEndpoints[] = "modeler/{$assetId}";
            } elseif ($assetType === Screen::class) {
                $allowedEndpoints[] = "designer/screen-builder/{$assetId}/edit";
                $allowedEndpoints[] = "designer/screens/{$assetId}/edit";
                $allowedEndpoints[] = 'designer/screens/preview';
            } elseif ($assetType === Script::class) {
                $allowedEndpoints[] = "designer/scripts/{$assetId}/builder";
                $allowedEndpoints[] = "designer/scripts/{$assetId}/edit";
                $allowedEndpoints[] = 'designer/scripts/preview';
            }

            if (class_exists($dataSourceClass) && $assetType === $dataSourceClass) {
                $allowedEndpoints[] = "designer/data-sources/{$assetId}/edit";
            }
            if (class_exists($decisionTableClass) && $assetType === $decisionTableClass) {
                $allowedEndpoints[] = "decision-tables/table-builder/{$assetId}/edit";
            }
        }

        return Str::contains($currentPath, $allowedEndpoints);
    }

    private function isProjectAsset($permission, $params)
    {
        if ($params && $params[0]) {
            return $this->handleUpdateDeleteOperations($permission, class_basename($params[0]));
        }

        return $this->checkForListCreateOperations($permission);
    }

    private function handleUpdateDeleteOperations($permission, $modelClass)
    {
        $asset = $this->getAssetName($modelClass);

        return $this->checkPermissionForAsset($permission, $asset);
    }

    /**
     * Get the asset name based on the model class.
     *
     * @param string $modelClass
     * @return string
     */
    private function getAssetName($modelClass)
    {
        $asset = Str::snake(class_basename($modelClass));

        // Adjust asset name for DataSource class
        if ($modelClass === 'DataSource') {
            $asset = 'data-source';
        }

        return $asset;
    }

    private function checkForListCreateOperations($permission)
    {
        $projectAssetTypes = ['process', 'screen', 'script', 'data-source', 'decision_table', 'pm-block'];

        foreach ($projectAssetTypes as $asset) {
            if (Str::contains($permission->name, $asset)) {
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
