<?php

namespace ProcessMaker\Providers;

use Illuminate\Auth\RequestGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\PmTable;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\TaskUser;
use ProcessMaker\Model\Script;
use ProcessMaker\Policies\ApplicationPolicy;
use ProcessMaker\Policies\AssigneeTaskPolicy;
use ProcessMaker\Policies\FormPolicy;
use ProcessMaker\Policies\InputDocumentPolicy;
use ProcessMaker\Policies\OutputDocumentPolicy;
use ProcessMaker\Policies\PmTablePolicy;
use ProcessMaker\Policies\ProcessCategoryPolicy;
use ProcessMaker\Policies\ProcessPolicy;
use ProcessMaker\Policies\ProcessVariablePolicy;
use ProcessMaker\Policies\ReportTablePolicy;
use ProcessMaker\Policies\TaskPolicy;
use ProcessMaker\Policies\ScriptPolicy;

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
        ReportTable::class => ReportTablePolicy::class,
        Form::class => FormPolicy::class,
        Script::class => ScriptPolicy::class,
        Script::class => ScriptPolicy::class,
        TaskUser::class => AssigneeTaskPolicy::class,
        Delegation::class => AssigneeTaskPolicy::class,
        InputDocument::class => InputDocumentPolicy::class,
        OutputDocument::class => OutputDocumentPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerPolicies();

        Passport::routes();

        Gate::define('has-permission', function ($user, $permissions) {
            // Convert permissions to array and trimmed
            $permissions = explode(',', $permissions);
            array_walk($permissions, function (&$val) {
                $val = trim($val);
            });

            // First get user's role
            $role = $user->role;

            // Check for existence of role or if role is inactive
            if (!$role || $role->status != Role::STATUS_ACTIVE) {
                return false;
            }

            // Get all permissions for this role that is requested
            $validPermissionCount = $role->permissions()
                ->whereIn('code', $permissions)
                ->count();

            if ($validPermissionCount != count($permissions)) {
                // Then the number of permissions for the role that matched do not match the count of permissions
                // requested
                return false;
            }

            return true;
        });
    }

}
