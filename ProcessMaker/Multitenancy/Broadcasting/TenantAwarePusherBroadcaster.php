<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Pusher\Pusher;

class TenantAwarePusherBroadcaster extends PusherBroadcaster
{
    private int $tenantId;

    public function __construct(Pusher $pusher, int $tenantId)
    {
        parent::__construct($pusher);
        $this->tenantId = $tenantId;
    }

    public function channel($channel, $callback, $options = [])
    {
        $channel = "tenant_{$this->tenantId}.{$channel}";

        return parent::channel($channel, $callback, $options);
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
