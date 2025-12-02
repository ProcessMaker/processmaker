<?php

namespace ProcessMaker\Multitenancy;

use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction as BaseMakeQueueTenantAwareAction;

class MakeQueueTenantAwareAction extends BaseMakeQueueTenantAwareAction
{
    /**
     * We're handling tenant aware queues manually, however, we still need to implement this because for some
     * reason the Spatie package calls it in Multitenancy::start(), weather it's a configured action or not.
     */
    public function execute() : void
    {
        // Do nothing
    }
}
