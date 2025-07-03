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
        $tenant = null;
        $message = null;

        /**
         * This could be a console command disguised as an http request (APP_RUNNING_IN_CONSOLE=false)
         * Check if we have a TENANT env variable set.
         * See ProcessMakerServiceProvider::setCurrentTenantForConsoleCommands() for non-disguised console commands
         */
        if (Env::get('TENANT')) {
            $tenant = app(IsTenant::class)::findOrFail(Env::get('TENANT'));
            if (!$tenant) {
                $message = 'No tenant found for TENANT: ' . Env::get('TENANT');
            }
        }

        if (!$tenant) {
            try {
                $tenant = parent::findForRequest($request);
            } catch (\Illuminate\Database\QueryException $_e) {
            }
            if (!$tenant) {
                $message = 'No tenant found for host: ' . $request->getHost();
            }
        }

        if (!$tenant) {
            // Check if there is a database set for the processmaker connection.
            // If so, multitenancy is not enabled for this instance and we should
            // treat it as a regular request without a tenant.
            if (Env::get('DB_DATABASE') === null) {
                abort(499, $message);
            }
        }

        return $tenant;
    }
}
