<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Policies\MediaPolicy;
use ProcessMaker\Policies\ProcessPolicy;
use ProcessMaker\Policies\ProcessRequestPolicy;
use ProcessMaker\Policies\ProcessRequestTokenPolicy;
use ProcessMaker\Policies\ProcessVersionPolicy;
use ProcessMaker\Policies\ScriptPolicy;
use ProcessMaker\Policies\UserPolicy;

/**
 * Our AuthService Provider binds our base processmaker provider and registers any policies, if defined.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Media::class => MediaPolicy::class,
        Process::class => ProcessPolicy::class,
        ProcessVersion::class => ProcessVersionPolicy::class,
        ProcessRequest::class => ProcessRequestPolicy::class,
        ProcessRequestToken::class => ProcessRequestTokenPolicy::class,
        User::class => UserPolicy::class,
        Script::class => ScriptPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if ($user->is_administrator) {
                return true;
            }

            // Let other policies handle the request.
            return null;
        });

        try {
            $permissions = Permission::select('name')->get();
            // Define the Gate permissions
            $permissions->each(function ($permission) {
                Gate::define($permission->name, function (User $user, ...$params) use ($permission) {
                    $authorized = false;

                    // Check if the user has the permission
                    if ($user->hasPermission($permission->name)) {
                        return true;
                    }

                    // If the user has no projects, return false.
                    $projects = $this->getProjectsForUser($user->id);
                    if (empty($projects)) {
                        return false;
                    }

                    // Check if the user has 'create-projects' permission and the request is from specific endpoints
                    // Users that ONLY have 'create-projects' permission are allowed to access specific endpoints
                    $isAllowedEndpoint = $this->checkAllowedEndpoints($projects, request()->path());
                    if ($user->hasPermission('create-projects') && $isAllowedEndpoint) {
                        $authorized = $this->isProjectAsset($permission, $params);
                    }

                    return $authorized;
                });
            });
        } catch (\Exception $e) {
            Log::notice('Unable to register gates. Either no database connection or no permissions table exists.');
        }

        Auth::viaRequest('anon', function ($request) {
            if ($request->user()) {
                return $request->user();
            }

            return app(AnonymousUser::class);
        });
    }

    /**
     * Retrieves the projects associated with the user.
     */
    private function getProjectsForUser(int $userId): array
    {
        if (!Schema::hasTable('projects')) {
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
        return Str::contains($permission->name, $asset);
    }
}
