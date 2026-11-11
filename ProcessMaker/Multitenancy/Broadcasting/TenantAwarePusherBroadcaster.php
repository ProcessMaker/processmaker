<?php

namespace ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantAwarePusherBroadcaster extends PusherBroadcaster
{
    /**
     * Authenticate the incoming request for a given channel.
     *
     * Channel callbacks are registered without a tenant prefix (once per Octane
     * worker). Incoming Echo channels are prefixed, so strip the current
     * tenant's prefix before matching. Pusher still signs the original name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);
        $channelName = $this->unprefixTenantChannel($channelName);

        if (empty($request->channel_name) ||
            ($this->isGuardedChannel($request->channel_name) &&
            !$this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return $this->prefixTenantChannel((string) $channel);
        }, $channels);
    }

    private function currentTenantId(): ?int
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        return $tenant?->id ? (int) $tenant->id : null;
    }

    private function tenantPrefix(): ?string
    {
        $tenantId = $this->currentTenantId();

        return $tenantId ? "tenant_{$tenantId}." : null;
    }

    private function prefixTenantChannel(string $channel): string
    {
        $prefix = $this->tenantPrefix();
        if ($prefix === null) {
            return $channel;
        }

        foreach (['private-encrypted-', 'private-', 'presence-'] as $guardPrefix) {
            if (str_starts_with($channel, $guardPrefix)) {
                $name = substr($channel, strlen($guardPrefix));
                if (str_starts_with($name, $prefix)) {
                    return $channel;
                }

                return $guardPrefix . $prefix . $name;
            }
        }

        if (str_starts_with($channel, $prefix)) {
            return $channel;
        }

        return $prefix . $channel;
    }

    private function unprefixTenantChannel(string $channelName): string
    {
        $prefix = $this->tenantPrefix();
        if ($prefix === null) {
            return $channelName;
        }

        if (!str_starts_with($channelName, $prefix)) {
            throw new AccessDeniedHttpException;
        }

        return substr($channelName, strlen($prefix));
    }
}
