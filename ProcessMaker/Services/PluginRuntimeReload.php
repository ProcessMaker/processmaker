<?php

namespace ProcessMaker\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;

/**
 * Tells every pod to recycle its local supervisor programs after a plugin change.
 */
class PluginRuntimeReload
{
    /**
     * Store a new reload version and notify listeners.
     */
    public function publish(string $pluginName): int
    {
        $version = (int) Redis::incr($this->versionKey());

        Redis::publish($this->channel(), json_encode([
            'version' => $version,
            'plugin' => $pluginName,
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

    /**
     * Run supervisorctl restart in a detached process.
     *
     * The listener must not wait for supervisorctl. `restart all` stops every
     * program on this pod, including this listener when supervisor manages it.
     */
    public function scheduleSupervisorRestart(): bool
    {
        $delay = max(0, (int) config('plugin_manager.supervisor_restart_delay', 5));
        $launcher = 'sleep ' . $delay . '; ' . implode(' ', array_map('escapeshellarg', $this->supervisorCommand()));
        $script = 'setsid nohup bash -c ' . escapeshellarg($launcher) . ' >/dev/null 2>&1 </dev/null &';

        try {
            $process = Process::timeout(15)->run(['bash', '-c', $script]);
        } catch (ProcessTimedOutException) {
            return false;
        }

        return $process->successful();
    }

    /**
     * @return list<string>
     */
    public function supervisorCommand(): array
    {
        $command = [
            config('plugin_manager.supervisorctl_bin', 'supervisorctl'),
        ];

        $configFile = config('plugin_manager.supervisorctl_config');
        if ($configFile) {
            $command[] = '-c';
            $command[] = $configFile;
        }

        $command[] = 'restart';
        $command[] = config('plugin_manager.supervisor_restart_target', 'all');

        return $command;
    }

    /**
     * Pub/sub channel names are not prefixed by PhpRedis. Include REDIS_PREFIX
     * and the tenant prefix so other instances on the shared Redis do not hear it.
     */
    public function channel(): string
    {
        return $this->redisPrefix() . $this->tenantPrefix() . config('plugin_manager.reload_channel', 'plugin-runtime-reload');
    }

    /**
     * INCR/GET receive REDIS_PREFIX from the PhpRedis client. Add only the tenant
     * prefix here so two tenants in one instance do not share the version.
     */
    public function versionKey(): string
    {
        return $this->tenantPrefix() . config('plugin_manager.reload_version_key', 'plugin-runtime-reload:version');
    }

    public function stateFile(): string
    {
        $path = (string) config('plugin_manager.reload_state_file', '/tmp/processmaker-plugin-reload-version');
        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return $path;
        }

        return $path . '-' . $tenantId;
    }

    private function redisPrefix(): string
    {
        return (string) config('database.redis.options.prefix', '');
    }

    private function tenantPrefix(): string
    {
        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return '';
        }

        return 'tenant_' . $tenantId . ':';
    }

    private function tenantId(): ?string
    {
        if (!app()->bound('currentTenant')) {
            return null;
        }

        $tenant = app('currentTenant');
        if (!$tenant) {
            return null;
        }

        $tenantId = method_exists($tenant, 'getKey') ? $tenant->getKey() : ($tenant->id ?? null);
        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return (string) $tenantId;
    }
}
