<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Exception\PluginInstallException;
use ProcessMaker\Exception\PluginValidationException;

class PluginSetupService
{
    public function __construct(
        private readonly PluginLogService $log,
        private readonly PluginRuntimeService $runtime,
    ) {
    }

    public function finish(string $path, string $name, ?int $userId = null): void
    {
        $this->log->running('Validating plugin...', $name, $userId);
        $this->validate($path, $name, $userId);
        $this->log->running('Installing dependencies...', $name, $userId);
        set_time_limit(0);
        $process = Process::path($path)->forever()->env([
            'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:' . (getenv('PATH') ?: ''),
        ])->run(['composer', 'install', '--no-interaction']);
        if (!$process->successful()) {
            $this->log->error('Failed to run composer install: ' . $process->errorOutput(), $name, $userId);
            throw PluginInstallException::failedToRunComposerInstall($process->errorOutput());
        }
        $command = "{$name}:install";
        $this->log->running('installCommand: ' . $command, $name, $userId);
        $this->log->running('Running plugin install command...', $name, $userId);
        $this->runtime->command($command, $name, $userId);
        $this->publishAssets($path, $name, $userId);
        $this->runtime->refresh($name, $userId);
    }

    public function validate(string $path, string $name, ?int $userId = null): void
    {
        $composerPath = $path . '/composer.json';
        if (!file_exists($composerPath)) {
            $this->log->error('composer.json not found in plugin: ' . $path, $name, $userId);
            throw PluginValidationException::composerJsonNotFound($path);
        }
        $content = file_get_contents($composerPath);
        $composer = json_decode($content ?: '', true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log->error('Invalid JSON in composer.json at ' . $composerPath . ': ' . json_last_error_msg(), $name, $userId);
            throw PluginValidationException::invalidComposerJson($composerPath, json_last_error_msg());
        }
        $changed = false;
        foreach (['require', 'require-dev'] as $key) {
            if (Arr::get($composer, $key, []) === []) {
                $composer[$key] = new \stdClass();
                $changed = true;
            }
        }
        if ($changed) {
            file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT));
        }
        $namespaces = Arr::get($composer, 'autoload.psr-4', []);
        if (empty($namespaces)) {
            $this->log->error('Plugin must have PSR-4 autoload configuration in composer.json', $name, $userId);
            throw PluginValidationException::missingPsr4Autoload();
        }
        $prefixes = ['ProcessMaker\\Plugins\\', 'ProcessMaker\\Package\\', 'ProcessMaker\\Packages\\'];
        $valid = false;
        foreach ($namespaces as $namespace => $pathValue) {
            $valid = (bool) collect($prefixes)->first(fn ($prefix) => str_starts_with($namespace, $prefix));
            if ($valid) {
                break;
            }
        }
        if (!$valid) {
            $this->log->error('Plugin PSR-4 namespace must start with ' . implode(', ', $prefixes) . '. Found: ' . implode(', ', array_keys($namespaces)), $name, $userId);
            throw PluginValidationException::invalidPsr4Namespace(implode(', ', array_keys($namespaces)));
        }
    }

    private function publishAssets(string $path, string $name, ?int $userId): void
    {
        if (!\ProcessMaker\Support\PluginVendorAssets::isEnabled()) {
            return;
        }
        try {
            $published = app(\ProcessMaker\Managers\TenantPluginAssetPublisher::class)->publishPlugin($path);
            $this->log->running($published ? 'Tenant plugin public assets published.' : 'Tenant plugin assets skipped: no public directory.', $name, $userId);
        } catch (\Throwable $exception) {
            $this->log->error('Could not publish tenant plugin assets: ' . $exception->getMessage(), $name, $userId);
        }
    }
}
