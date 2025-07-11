<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;

class TenantAwareBroadcastManager extends BroadcastManager
{
    public function createPusherDriver($config)
    {
        return new TenantAwarePusherBroadcaster($this->pusher($config));
    }

    public function channel($name, $callback)
    {
        $tenantId = app('currentTenant')?->id;
        if ($tenantId) {
            $name = "tenant_{$tenantId}.{$name}";
        }

        return parent::channel($name, $callback);
    }
}
