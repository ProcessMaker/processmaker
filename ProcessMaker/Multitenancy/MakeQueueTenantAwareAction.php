<?php

namespace ProcessMaker\Multitenancy;

use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction as BaseMakeQueueTenantAwareAction;

class MakeQueueTenantAwareAction extends BaseMakeQueueTenantAwareAction
{
    /**
     * Non-multitenant environments shouldn't throw an exception if the tenant is not found.
     */
    public function execute() : void
    {
        if (!config('app.multitenancy')) {
            return;
        }

        parent::execute();
    }
}
