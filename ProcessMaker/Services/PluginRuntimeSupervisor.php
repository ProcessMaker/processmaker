<?php

namespace ProcessMaker\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Multitenancy\Services\OctaneRuntimeRenderer;
use Throwable;

/**
 * Controls local Supervisor programs after a plugin runtime change.
 */
class PluginRuntimeSupervisor
{
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
     */
    public function scheduleSupervisorRestart(?string $tenantId = null): bool
    {
        $delay = max(0, (int) config('plugin_manager.supervisor_restart_delay', 5));
        $launcher = 'sleep ' . $delay . '; ' . implode(' ', array_map(
            'escapeshellarg',
            $this->supervisorCommand($tenantId)
        ));
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

    /**
     * @return list<string>
     */
    public function supervisorCommand(?string $tenantId = null): array
    {
        return [
            ...$this->pluginSupervisorctlBase(),
            'restart',
            $this->supervisorRestartTarget($tenantId),
        ];
    }

    public function supervisorRestartTarget(?string $tenantId = null): string
    {
        if ($tenantId !== null && preg_match('/^\d+$/', $tenantId)) {
            return $this->octaneSupervisorRestartTarget(
                (new OctaneRuntimeRenderer())->tenantProgramName((int) $tenantId)
            );
        }

        return config('plugin_manager.supervisor_restart_target', 'all');
    }

    /**
     * @return list<string>
     */
    public function octaneSupervisorRestartCommand(string $program): array
    {
        return [
            ...$this->octaneSupervisorctlBase(),
            'restart',
            $this->octaneSupervisorRestartTarget($program),
        ];
    }

    public function octaneSupervisorRestartTarget(string $program): string
    {
        return $program . ':*';
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
