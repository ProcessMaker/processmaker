<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use ProcessMaker\Events\TenantResolved;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
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

        // Explicitly disable client UUIDs to match the database schema (integer id)
        // In newer versions of Passport, UUIDs are enabled by default
        Passport::$clientUuids = false;

        Passport::enablePasswordGrant();

        Passport::authorizationView('auth.oauth2.authorize');

        Gate::before(function ($user) {
            if ($user->is_administrator) {
                return true;
            }

            // Let other policies handle the request.
            return null;
        });

        Auth::viaRequest('anon', function ($request) {
            if ($request->user()) {
                return $request->user();
            }

            return app(AnonymousUser::class);
        });
    }

    public function defineGates()
    {
        // No need to run this in console and it generates
        // errors in multitenancy mode
        if (!app()->environment('testing') && app()->runningInConsole()) {
            return;
        }

        try {
            // Cache the permissions for a day to improve performance
            $permissions = Cache::remember('permissions', 86400, function () {
                return Permission::pluck('name')->toArray();
            });
            foreach ($permissions as $permission) {
                Gate::define($permission, function ($user) use ($permission) {
                    return $user->hasPermission($permission);
                });
            }

            Gate::define('view-designer', function ($user) {
                return $user->canAny('view-processes|view-process-categories|view-scripts|view-screens|view-environment_variables|view-projects');
            });
        } catch (\Exception $e) {
            Log::notice('Unable to register gates. Either no database connection or no permissions table exists.');
        }
    }

    public function register()
    {
        Event::listen(TenantResolved::class, function ($tenant) {
            $this->defineGates();
        });
    }
}
