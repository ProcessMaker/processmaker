<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Bus\Dispatcher;

class TenantAwareDispatcher extends Dispatcher
{
    private int $tenantId;

    public function __construct($app, $dispatcher, $tenantId)
    {
        parent::__construct($app, $dispatcher->queueResolver); // we need to pass the queueResolver
        $this->tenantId = $tenantId;
    }

    public function dispatchToQueue($command)
    {
        $queue = $command->queue;

        // We need to set the default queue here
        // because prepending the tenant id means
        // it will no longer be empty.
        if (empty($queue)) {
            $queue = 'default';
        }
        $command->queue = 'tenant-' . $this->tenantId . '-' . $queue;

        return parent::dispatchToQueue($command);
    }
}
