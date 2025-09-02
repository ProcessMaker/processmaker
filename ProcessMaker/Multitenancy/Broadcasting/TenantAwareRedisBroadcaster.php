<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Contracts\Redis\Factory as Redis;

class TenantAwareRedisBroadcaster extends RedisBroadcaster
{
    private int $tenantId;

    public function __construct(Redis $redis, string $connection, int $tenantId)
    {
        parent::__construct($redis, $connection);
        $this->tenantId = $tenantId;
    }

    protected function formatChannels(array $channels)
    {
        $channels = array_map(function ($channel) {
            $channel = (string) $channel;
            if ($this->tenantId) {
                // Check if channel starts with "private-"
                if (str_starts_with($channel, 'private-')) {
                    return "private-tenant_{$this->tenantId}." . substr($channel, 8); // Remove "private-" prefix and add tenant before the rest
                }

                return "tenant_{$this->tenantId}.{$channel}";
            }

            return $channel;
        }, $channels);

        return $channels;
    }
}
