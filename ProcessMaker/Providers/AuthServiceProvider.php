<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Policies\MediaPolicy;
use ProcessMaker\Policies\NotificationPolicy;
use ProcessMaker\Policies\ProcessPolicy;
use ProcessMaker\Policies\ProcessRequestPolicy;
use ProcessMaker\Policies\ProcessRequestTokenPolicy;
use ProcessMaker\Policies\ProcessVersionPolicy;
use ProcessMaker\Policies\ScriptPolicy;
use ProcessMaker\Policies\UserPolicy;

/**
 * Our AuthService Provider binds our base processmaker provider and registers any policies, if defined.
 * @todo Add gates to provide authorization functionality. See branch release/3.3 for sample implementations
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
        Notification::class => NotificationPolicy::class,
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
        });

        try {
            $permissions = Permission::select('name')->get();

            // Define the Gate permissions
            $permissions->each(function ($permission) {
                Gate::define($permission->name, function (User $user, ...$params) use ($permission) {
                    // Check if the user has the permission
                    if ($user->hasPermission($permission->name)) {
                        return true;
                    }

                    // Check if the user has 'create-projects' permission and the request is from specific endpoints
                    // Users that ONLY have 'create-projects' permission are allowed to access specific endpoints
                    $isAllowedEndpoint = $this->checkAllowedEndpoints(request()->path());

                    if ($user->hasPermission('create-projects') && $isAllowedEndpoint) {
                        return $this->isProjectAsset($permission, $params);
                    }

                    return false;
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

    private function checkAllowedEndpoints($currentPath)
    {
        $allowedEndpoints = [
            'api',
            'designer/screen-builder',
            'modeler/',
            'script/',
            'designer/decision-tables',
            'designer/data-sources',
        ];
        foreach ($allowedEndpoints as $endpoint) {
            if (Str::startsWith($currentPath, $endpoint)) {
                return true;
            }
        }

        return false;
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
        $asset = Str::snake(class_basename($modelClass));

        return $this->checkPermissionForAsset($permission, $asset);
    }

    private function checkForListCreateOperations($permission)
    {
        $projectAssetTypes = ['process', 'screen', 'script', 'data-source', 'decision_table'];

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
