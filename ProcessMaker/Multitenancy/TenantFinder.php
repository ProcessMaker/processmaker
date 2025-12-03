<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use ProcessMaker\Multitenancy\Tenant;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

class TenantFinder extends DomainTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        if (!config('app.multitenancy')) {
            return null;
        }

        if ($tenant = Tenant::fromBootstrapper()) {
            return $tenant;
        }

        $tenant = null;
        $message = null;

        /**
         * This could be a console command disguised as an http request (APP_RUNNING_IN_CONSOLE=false)
         * Check if we have a TENANT env variable set.
         * See ProcessMakerServiceProvider::setCurrentTenantForConsoleCommands() for non-disguised console commands
         */
        if (Env::get('TENANT')) {
            $tenant = app(IsTenant::class)::findOrFail(Env::get('TENANT'));
        }

        if (!$tenant) {
            try {
                $tenant = parent::findForRequest($request);
            } catch (\Illuminate\Database\QueryException $_e) {
            }
        }

        return $tenant;
    }
}
