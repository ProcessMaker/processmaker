<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

class TenantFinder extends DomainTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        /**
         * This could be a console command disguised as an http request (APP_RUNNING_IN_CONSOLE=false)
         * Check if we have a TENANT env variable set.
         * See ProcessMakerServiceProvider::setCurrentTenantForConsoleCommands() for non-disguised console commands
         */
        if (Env::get('TENANT')) {
            return app(IsTenant::class)::findOrFail(Env::get('TENANT'));
        }

        return parent::findForRequest($request);
    }
}
