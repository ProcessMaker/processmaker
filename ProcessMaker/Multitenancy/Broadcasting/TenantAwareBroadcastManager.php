<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;

class TenantAwareBroadcastManager extends BroadcastManager
{
    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createPusherDriver(array $config)
    {
        return new TenantAwarePusherBroadcaster($this->pusher($config), $config['jsonp'] ?? false);
    }
}
