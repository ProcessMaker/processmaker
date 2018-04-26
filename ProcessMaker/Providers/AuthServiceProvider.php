<?php
namespace ProcessMaker\Providers;

use Illuminate\Auth\RequestGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use League\OAuth2\Server\ResourceServer;
use ProcessMaker\Guards\OAuth2Guard;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\Role;
use ProcessMaker\OAuth2\AccessTokenRepository;
use ProcessMaker\OAuth2\ClientRepository;
use ProcessMaker\Policies\ApplicationPolicy;
use ProcessMaker\Policies\PmTablePolicy;
use ProcessMaker\Policies\ProcessCategoryPolicy;
use ProcessMaker\Policies\ProcessPolicy;
use ProcessMaker\Policies\ProcessVariablePolicy;
use ProcessMaker\Policies\ReportTablePolicy;

/**
 * Our AuthService Provider binds our base processmaker provider and registers any policies, if defined.
 * @package ProcessMaker\Providers
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
        Application::class => ApplicationPolicy::class,
        Process::class => ProcessPolicy::class,
        ProcessCategory::class => ProcessCategoryPolicy::class,
        PmTable::class => PmTablePolicy::class,
        ProcessVariable::class => ProcessVariablePolicy::class,
        ReportTable::class => ReportTablePolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerPolicies();

        Gate::define('has-permission', function ($user, $permissions) {
            // Convert permissions to array and trimmed
            $permissions = explode(',', $permissions);
            array_walk($permissions, function (&$val) {
                $val = trim($val);
            });

            // First get user's role
            $role = $user->role;

            // Check for existence of role or if role is disabled
            if (!$role || $role->ROL_STATUS != Role::STATUS_ACTIVE) {
                return false;
            }

            // Get all permissions for this role that is requested
            $validPermissionCount = $role->permissions()
                ->whereIn('PER_CODE', $permissions)
                ->count();

            if ($validPermissionCount != count($permissions)) {
                // Then the number of permissions for the role that matched do not match the count of permissions
                // requested
                return false;
            }

            return true;
        });
    }

    /**
     * Register our processmaker user provider which will assist in finding users
     */
    public function register()
    {
        // Register our base processmaker user provider
        Auth::provider('processmaker', function ($app, array $config) {
            return new UserProvider($app->make('hash'));
        });

        $this->registerGuard();
    }

    /**
     * Register the oauth2 token guard.
     */
    protected function registerGuard()
    {
        Auth::extend('processmaker-oauth2', function ($app, $name, array $config) {
            return tap($this->makeGuard($config), function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            });
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new OAuth2Guard(
                $this->app->make(ResourceServer::class),
                Auth::createUserProvider($config['provider']),
                $this->app->make(AccessTokenRepository::class),
                $this->app->make(ClientRepository::class),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }
}
