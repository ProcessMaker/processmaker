<?php

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Tells every pod to recycle its local supervisor programs after a plugin change.
 */
class PluginRuntimeReload
{
    /**
     * Store a new reload version and notify listeners.
     */
    public function publish(string $pluginName, ?string $tenantId = null): int
    {
        $version = (int) Redis::incr($this->versionKey());
        $tenantId ??= $this->tenantId();

        Redis::publish($this->channel(), json_encode([
            'version' => $version,
            'plugin' => $pluginName,
            'tenant_id' => $tenantId,
        ]));

        return $version;
    }

    public function remoteVersion(): int
    {
        return (int) Redis::get($this->versionKey());
    }

    public function appliedVersion(): int
    {
        $path = $this->stateFile();
        if (!is_file($path)) {
            return 0;
        }

        return (int) file_get_contents($path);
    }

    /**
     * Record the version on this pod so a later startup does not restart again.
     */
    public function rememberApplied(int $version): bool
    {
        if ($version <= $this->appliedVersion()) {
            return true;
        }

        return file_put_contents($this->stateFile(), (string) $version) !== false;
    }

    public function versionFromMessage(string $message): int
    {
        $payload = json_decode($message, true);
        if (!is_array($payload)) {
            return 0;
        }

        return (int) ($payload['version'] ?? 0);
    }

    public function tenantIdFromMessage(string $message): ?string
    {
        $payload = json_decode($message, true);
        if (!is_array($payload)) {
            return null;
        }

        $tenantId = $payload['tenant_id'] ?? null;
        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return is_scalar($tenantId) && preg_match('/^\d+$/', (string) $tenantId)
            ? (string) $tenantId
            : null;
    }

    /**
     * Restart this pod's Octane program and recycle Horizon workers.
     */
    public function recycleLocalRuntime(): bool
    {
        return app(PluginRuntimeSupervisor::class)->recycleLocalRuntime();
    }

    public function scheduleSupervisorRestart(?string $tenantId = null): bool
    {
        return app(PluginRuntimeSupervisor::class)->scheduleSupervisorRestart($tenantId);
    }

    /**
     * Pub/sub channel names are not prefixed by PhpRedis. Include REDIS_PREFIX
     * so instances using the same Redis prefix receive the same notification.
     */
    public function channel(): string
    {
        return $this->redisPrefix() . config('plugin_manager.reload_channel', 'plugin-runtime-reload');
    }

    /**
     * INCR/GET receive REDIS_PREFIX from the PhpRedis client. The version is
     * global because all tenant reloads share one pub/sub channel.
     */
    public function versionKey(): string
    {
        return config('plugin_manager.reload_version_key', 'plugin-runtime-reload:version');
    }

    public function stateFile(): string
    {
        return (string) config('plugin_manager.reload_state_file', '/tmp/processmaker-plugin-reload-version');
    }

    private function redisPrefix(): string
    {
        return (string) config('database.redis.options.prefix', '');
    }

    private function tenantId(): ?string
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        if (!$tenant) {
            return null;
        }

        $tenantId = method_exists($tenant, 'getKey') ? $tenant->getKey() : ($tenant->id ?? null);

        return ($tenantId === null || $tenantId === '') ? null : (string) $tenantId;
    }
}
