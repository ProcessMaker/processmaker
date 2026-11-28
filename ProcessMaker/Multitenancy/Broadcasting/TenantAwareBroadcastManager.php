<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Redis\Factory as Redis;

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

    public function createRedisDriver($config)
    {
        return new TenantAwareRedisBroadcaster(
            $this->app->make(Redis::class),
            $config['connection'] ?? 'default',
            $this->tenantId
        );
    }
}
