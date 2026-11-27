<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use function Illuminate\Support\php_binary;
use ProcessMaker\Services\PluginRuntimeReload;
use Throwable;

class PluginRuntimeService
{
    public function __construct(
        private readonly PluginLogService $log,
        private readonly PluginRuntimeReload $reload,
    ) {
    }

    public function command(string $command, string $pluginName, ?int $userId = null): void
    {
        $result = Process::env($this->environment())->timeout(300)->run(array_filter([
            php_binary(), base_path('artisan'), $command,
        ]));
        if (!$result->successful()) {
            $result->output() && str_contains($result->output(), 'is not defined')
                ? $this->log->running("Plugin does not have a {$command} command", $pluginName, $userId)
                : $this->log->error("Plugin {$command} command failed. Got output:\n\n{$result->output()}", $pluginName, $userId);
            return;
        }
        $this->log->running("Plugin {$command} command output:\n\n{$result->output()}", $pluginName, $userId);
    }

    public function refresh(string $pluginName, ?int $userId = null): void
    {
        $this->log->running('Rebuilding route cache...', $pluginName, $userId);
        $this->command('route:cache', $pluginName, $userId);
        $cachedRoutes = app()->getCachedRoutesPath();
        if (is_file($cachedRoutes)) {
            require $cachedRoutes;
        }
        $this->log->running('Recycling local Octane and Horizon on this pod...', $pluginName, $userId);
        try {
            if ($this->reload->recycleLocalRuntime()) {
                $this->log->running('Local Octane and Horizon recycle completed on this pod.', $pluginName, $userId);
                return;
            }
        } catch (Throwable $exception) {
            $this->log->error('Could not recycle local Octane and Horizon: ' . $exception->getMessage(), $pluginName, $userId);
        }
        $this->log->running('Falling back to plugin runtime reload broadcast for other pods.', $pluginName, $userId);
        try {
            $version = $this->reload->publish($pluginName, $this->tenantId());
            $this->log->running("Plugin runtime reload published (version {$version}). Each pod restarts its own supervisor programs.", $pluginName, $userId);
        } catch (Throwable $exception) {
            $this->log->error('Could not publish the plugin runtime reload: ' . $exception->getMessage(), $pluginName, $userId);
        }
    }

    public function environment(): array
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        return config('app.multitenancy') && $tenant ? ['TENANT' => (string) $tenant->id] : [];
    }

    public function tenantId(): ?string
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        if (!$tenant) {
            return null;
        }
        $id = method_exists($tenant, 'getKey') ? $tenant->getKey() : ($tenant->id ?? null);

        return $id === null || $id === '' ? null : (string) $id;
    }
}
