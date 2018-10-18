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
    }

}
