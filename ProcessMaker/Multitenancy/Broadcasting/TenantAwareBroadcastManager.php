<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;

class TenantAwareBroadcastManager extends BroadcastManager
{
    private int $tenantId;

    public function __construct($app, int $tenantId)
    {
        parent::__construct($app);
        $this->tenantId = $tenantId;
    }

    public function createPusherDriver($config)
    {
        return new TenantAwarePusherBroadcaster($this->pusher($config), $this->tenantId);
    }
}
