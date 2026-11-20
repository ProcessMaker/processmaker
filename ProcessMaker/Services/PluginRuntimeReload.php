<?php

namespace ProcessMaker\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Multitenancy\Services\OctaneRuntimeRenderer;
use Throwable;

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
     * Restart this pod's Octane program and recycle Horizon workers.
     */
    public function recycleLocalRuntime(): bool
    {
        $program = $this->octaneSupervisorProgram();
        if ($program === null) {
            return false;
        }

        if (!$this->runSupervisorRestart($program)) {
            return false;
        }

        return $this->terminateHorizon();
    }

    public function terminateHorizon(): bool
    {
        try {
            return Artisan::call('horizon:terminate', ['--no-interaction' => true]) === 0;
        } catch (Throwable) {
            return false;
        }
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
        $shell = $this->supervisorRestartShell();
        $script = 'nohup ' . $shell . ' -c ' . escapeshellarg($launcher) . ' >/dev/null 2>&1 </dev/null &';

        foreach (['/usr/bin/setsid', '/bin/setsid'] as $setsid) {
            if (is_executable($setsid)) {
                $script = $setsid . ' ' . $script;
                break;
            }
        }

        try {
            $process = Process::timeout(15)->run([$shell, '-c', $script]);
        } catch (ProcessTimedOutException) {
            return false;
        }

        return $process->successful();
    }

    private function supervisorRestartShell(): string
    {
        $configured = config('plugin_manager.supervisor_restart_shell');
        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        foreach (['/bin/bash', '/usr/bin/bash', '/bin/sh', '/usr/bin/sh'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return '/bin/sh';
    }

    /**
     * @return list<string>
     */
    public function supervisorCommand(): array
    {
        return [
            ...$this->pluginSupervisorctlBase(),
            'restart',
            config('plugin_manager.supervisor_restart_target', 'all'),
        ];
    }

    /**
     * @return list<string>
     */
    public function octaneSupervisorRestartCommand(string $program): array
    {
        return [
            ...$this->octaneSupervisorctlBase(),
            'restart',
            $program,
        ];
    }

    private function runSupervisorRestart(string $program): bool
    {
        try {
            $process = Process::timeout(120)->run($this->octaneSupervisorRestartCommand($program));
        } catch (ProcessTimedOutException) {
            return false;
        }

        return $process->successful();
    }

    private function octaneSupervisorProgram(): ?string
    {
        if (!config('app.multitenancy')) {
            return OctaneRuntimeRenderer::SINGLE_PROGRAM;
        }

        $tenantId = $this->tenantId();
        if ($tenantId === null) {
            return null;
        }

        return (new OctaneRuntimeRenderer())->tenantProgramName((int) $tenantId);
    }

    /**
     * @return list<string>
     */
    private function pluginSupervisorctlBase(): array
    {
        $command = [
            config('plugin_manager.supervisorctl_bin', 'supervisorctl'),
        ];

        $configFile = config('plugin_manager.supervisorctl_config');
        if ($configFile) {
            $command[] = '-c';
            $command[] = $configFile;
        }

        return $command;
    }

    /**
     * @return list<string>
     */
    private function octaneSupervisorctlBase(): array
    {
        $command = [
            config(
                'octane_tenants.supervisorctl_bin',
                config('plugin_manager.supervisorctl_bin', 'supervisorctl')
            ),
        ];

        $configFile = config(
            'octane_tenants.supervisorctl_config',
            config('plugin_manager.supervisorctl_config')
        );
        if ($configFile) {
            $command[] = '-c';
            $command[] = $configFile;
        }

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
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        if (!$tenant) {
            return null;
        }

        $tenantId = method_exists($tenant, 'getKey') ? $tenant->getKey() : ($tenant->id ?? null);

        return ($tenantId === null || $tenantId === '') ? null : (string) $tenantId;
    }
}
