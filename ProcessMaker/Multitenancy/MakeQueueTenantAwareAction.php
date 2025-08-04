<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Facades\Context;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction as BaseMakeQueueTenantAwareAction;

class MakeQueueTenantAwareAction extends BaseMakeQueueTenantAwareAction
{
    /*
    * We need to override this method because spatie will throw an error if the tenant is not found.
    * However, we want to support non-multitenant instances. If the tenant is not found,
    * run the job without a tenant.
    */
    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        $tenantId = Context::get($this->currentTenantContextKey());
        if (!$tenantId) {
            // No need to do anything. Let the job run without a tenant.
            return;
        }

        parent::bindOrForgetCurrentTenant($event);
    }
}
