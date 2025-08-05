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
        $command->queue = 'tenant-' . $this->tenantId . '-' . $command->queue;

        return parent::dispatchToQueue($command);
    }
}
