<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        Passport::routes(function ($router) {
            $router->forAuthorization();
            $router->forAccessTokens();
            $router->forTransientTokens();
            $router->forClients();
            // Do NOT add routes for managing personal access tokens
            // As this is handled by our OWN api endpoints
        });

        Gate::before(function ($user) {
            if ($user->is_administrator) {
                return true;
            }
        });

        try {
            Permission::select('name')->get()->each(function ($permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    return $user->hasPermission($permission->name);
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
}
