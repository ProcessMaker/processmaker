<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;

class TenantAwarePusherBroadcaster extends PusherBroadcaster
{
    protected function formatChannels(array $channels)
    {
        $tenantId = app('currentTenant')?->id;
        $channels = array_map(function ($channel) use ($tenantId) {
            $channel = (string) $channel;
            if ($tenantId) {
                // Check if channel starts with "private-"
                if (str_starts_with($channel, 'private-')) {
                    return "private-tenant_{$tenantId}." . substr($channel, 8); // Remove "private-" prefix and add tenant before the rest
                }

                return "tenant_{$tenantId}.{$channel}";
            }

            return $channel;
        }, $channels);

        return $channels;
    }
}
