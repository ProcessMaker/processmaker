<?php

declare(strict_types=1);

namespace ProcessMaker\Managers;

use ProcessMaker\Exception\PluginInstallException;
use ProcessMaker\Exception\PluginNotFoundException;
use ProcessMaker\Exception\PluginReinstallException;
use ProcessMaker\Exception\PluginToggleException;
use ProcessMaker\Models\Plugin;
use ProcessMaker\Services\PluginFilesystemService;
use ProcessMaker\Services\PluginGitService;
use ProcessMaker\Services\PluginGitUpdateOptions;
use ProcessMaker\Services\PluginLogService;
use ProcessMaker\Services\PluginRuntimeService;
use ProcessMaker\Services\PluginSetupService;
use Throwable;

class PluginManager
{
    private ?string $incompleteInstallPath = null;

    private readonly PluginGitService $git;
    private readonly PluginFilesystemService $filesystem;
    private readonly PluginLogService $log;
    private readonly PluginRuntimeService $runtime;
    private readonly PluginSetupService $setup;

    public function __construct(
        ?PluginGitService $git = null,
        ?PluginFilesystemService $filesystem = null,
        ?PluginLogService $log = null,
        ?PluginRuntimeService $runtime = null,
        ?PluginSetupService $setup = null,
    ) {
        $this->git = $git ?? app(PluginGitService::class);
        $this->filesystem = $filesystem ?? app(PluginFilesystemService::class);
        $this->log = $log ?? app(PluginLogService::class);
        $this->runtime = $runtime ?? app(PluginRuntimeService::class);
        $this->setup = $setup ?? app(PluginSetupService::class);
    }

    public function install(string $repoUrl, ?string $branch = null, ?string $tag = null, ?int $userId = null, ?string $gitUsername = null, ?string $gitToken = null): void
    {
        if ($branch && $tag) {
            throw PluginInstallException::branchAndTagMutuallyExclusive();
        }
        $this->incompleteInstallPath = null;
        try {
            $local = config('plugin_manager.allow_local_install_path', false) ? $this->filesystem->resolveLocalPath($repoUrl) : null;
            if ($local !== null) {
                $name = basename($local);
                $path = storage_path('plugins') . '/' . $name;
                $this->log->running("Starting installation of plugin from local path: {$name}", $name, $userId);
                if (!$this->samePath($local, $path)) {
                    $this->copyIntoStorage($local, $path, $name, $userId);
                } else {
                    $this->log->running('Plugin is already installed at the target path', $name, $userId);
                }
            } else {
                [$path, $name] = $this->installRepository($repoUrl, $branch, $tag, $userId, $gitUsername, $gitToken);
            }
            $this->setup->finish($path, $name, $userId);
            $this->log->done('Plugin installed successfully', $name, $userId);
        } catch (Throwable $exception) {
            $this->revertFailedInstall($userId);
            throw $exception;
        }
    }

    public function reinstall(string $pluginName, ?int $userId = null, ?string $branch = null, ?string $tag = null, ?string $gitUsername = null, ?string $gitToken = null): void
    {
        $path = $this->installedPath($pluginName);
        if (!is_dir($path . '/.git')) {
            throw PluginReinstallException::notGitRepositoryFromRemote($pluginName);
        }
        $branch = $branch !== null && trim($branch) !== '' ? trim($branch) : null;
        $tag = $tag !== null && trim($tag) !== '' ? trim($tag) : null;
        if ($tag === null && $branch === null && ($branch = $this->git->currentBranch($path)) === null) {
            throw PluginReinstallException::notOnNamedBranch();
        }
        $canonical = $this->git->stripCredentials(Plugin::fromPath($path)->getUrl());
        $ref = $tag ?? $branch ?? 'current branch';
        $this->log->running("Starting reinstall of plugin: {$pluginName} on {$ref}", $pluginName, $userId);
        $this->git->update(
            $path,
            $pluginName,
            $userId,
            $this->log,
            new PluginGitUpdateOptions(
                $tag !== null ? null : $branch,
                $tag,
                $canonical,
                $this->git->prepareUrl($canonical, $gitUsername, $gitToken),
            ),
        );
        $this->setup->finish($path, $pluginName, $userId);
        $this->log->done('Plugin reinstalled successfully', $pluginName, $userId);
    }

    public function uninstall(string $pluginName, ?int $userId = null): void
    {
        $path = $this->installedPath($pluginName);
        $this->log->running("Starting uninstallation of plugin: {$pluginName}", $pluginName, $userId);
        $this->log->running('Running plugin uninstall command...', $pluginName, $userId);
        $this->runtime->command("{$pluginName}:uninstall", $pluginName, $userId);
        $this->removeAssets($path, $pluginName, $userId);
        $this->log->running('Removing plugin directory...', $pluginName, $userId);
        $this->filesystem->removePluginPath($path, $pluginName, $userId, $this->log);
        $this->runtime->refresh($pluginName, $userId);
        $this->log->done('Plugin uninstalled successfully', $pluginName, $userId);
    }

    public function installFromZip(string $zipPath, ?int $userId = null): void
    {
        if (!file_exists($zipPath)) {
            throw PluginInstallException::zipFileNotFound($zipPath);
        }
        $zip = new \ZipArchive();
        $result = $zip->open($zipPath);
        if ($result !== true) {
            throw PluginInstallException::failedToOpenZipFile($zipPath, $result);
        }
        $tmp = sys_get_temp_dir() . '/pm-plugin-' . uniqid();
        if (!mkdir($tmp, 0755, true)) {
            throw PluginInstallException::failedToCreateTempDirectory($tmp);
        }
        $name = basename($tmp);
        try {
            $zip->extractTo($tmp);
            $zip->close();
            $entries = array_diff(scandir($tmp), ['.', '..']);
            $source = count($entries) === 1 && is_dir($tmp . '/' . reset($entries)) ? $tmp . '/' . reset($entries) : $tmp;
            $name = basename($source);
            $this->setup->validate($source, $name, $userId);
            $path = storage_path('plugins') . '/' . $name;
            $this->log->running("Installing plugin from zip: {$name}", $name, $userId);
            is_dir(storage_path('plugins')) || mkdir(storage_path('plugins'), 0755, true);
            $this->markForRollback($path);
            if (is_dir($path)) {
                $this->filesystem->deleteDirectory($path, $name, $userId, $this->log);
            }
            $this->filesystem->copyDirectory($source, $path, $name, $userId, $this->log);
            $this->setup->finish($path, $name, $userId);
            $this->log->done('Plugin installed successfully', $name, $userId);
        } catch (Throwable $exception) {
            $this->revertFailedInstall($userId);
            throw $exception;
        } finally {
            is_dir($tmp) && $this->filesystem->deleteDirectory($tmp, $name, $userId, $this->log);
            file_exists($zipPath) && unlink($zipPath);
        }
    }

    public function toggle(string $pluginName, ?int $userId = null): bool
    {
        $folder = storage_path('plugins');
        $enabled = "{$folder}/{$pluginName}";
        $disabled = "{$folder}/_" . ltrim($pluginName, '_');
        if (is_dir($enabled)) {
            if (!rename($enabled, $disabled)) {
                $this->log->error("Failed to disable plugin: {$pluginName}", $pluginName, $userId);
                throw PluginToggleException::failedToDisable($pluginName);
            }
            $this->runtime->refresh($pluginName, $userId);
            return false;
        }
        if (is_dir($disabled)) {
            if (!rename($disabled, $enabled)) {
                $this->log->error("Failed to enable plugin: {$pluginName}", $pluginName, $userId);
                throw PluginToggleException::failedToEnable($pluginName);
            }
            $this->runtime->refresh(ltrim($pluginName, '_'), $userId);
            return true;
        }
        $this->log->error("Plugin not found: {$pluginName}", $pluginName, $userId);
        throw PluginNotFoundException::forPlugin($pluginName);
    }

    public function list(): array
    {
        $result = [];
        foreach (glob(storage_path('plugins') . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            $folder = basename($path);
            try {
                $plugin = Plugin::fromPath($path);
                $json = $plugin->getComposerJson();
                $result[] = ['name' => ltrim($folder, '_'), 'url' => $this->git->stripCredentials($plugin->getUrl()), 'folder' => $folder, 'description' => $plugin->getDescription() ?? 'No description', 'version' => $json['version'] ?? null, 'branch' => $plugin->getReference(), 'source' => $plugin->isGitRepository() ? 'git' : 'filesystem', 'enabled' => str_starts_with($folder, '_') ? 'Disabled' : 'Enabled'];
            } catch (\Exception) {
                continue;
            }
        }
        return $result;
    }

    public function revertFailedInstall(?int $userId = null): void
    {
        $path = $this->incompleteInstallPath;
        $this->incompleteInstallPath = null;
        if ($path === null || (!is_dir($path) && !is_link($path))) {
            return;
        }
        $name = basename($path);
        $this->log->running('Installation failed. Removing the incomplete plugin directory.', $name, $userId);
        try {
            $this->filesystem->removePluginPath($path, $name, $userId, $this->log);
        } catch (Throwable $exception) {
            $this->log->error('Could not remove the incomplete plugin directory: ' . $exception->getMessage(), $name, $userId);
        }
    }

    private function installRepository(string $url, ?string $branch, ?string $tag, ?int $userId, ?string $username, ?string $token): array
    {
        $name = $this->git->repositoryName($url);
        $path = storage_path('plugins') . '/' . $name;
        $authenticated = $this->git->prepareUrl($url, $username, $token);
        $this->log->running("Starting installation of plugin: {$name}", $name, $userId);
        if (is_dir($path)) {
            $this->git->update(
                $path,
                $name,
                $userId,
                $this->log,
                new PluginGitUpdateOptions($branch, $tag, $url, $authenticated),
            );
        } else {
            $this->markForRollback($path);
            $this->log->running('Cloning repository...', $name, $userId);
            $this->git->clone($authenticated, $path, $name, $userId, $url, $this->log);
            $branch && $this->git->checkoutBranch($path, $branch, $name, $userId, $this->log);
            $tag && $this->git->checkoutTag($path, $tag, $name, $userId, $this->log);
        }
        return [$path, $name];
    }

    private function installedPath(string $name): string
    {
        $base = storage_path('plugins');
        if (is_dir("{$base}/_{$name}") || is_link("{$base}/_{$name}")) {
            return "{$base}/_{$name}";
        }
        if (is_dir("{$base}/{$name}") || is_link("{$base}/{$name}")) {
            return "{$base}/{$name}";
        }
        throw PluginNotFoundException::forPlugin($name);
    }

    private function samePath(string $source, string $destination): bool
    {
        return ($resolvedSource = realpath($source)) && ($resolvedDestination = realpath($destination)) && $resolvedSource === $resolvedDestination && !is_link($destination);
    }

    private function copyIntoStorage(string $source, string $destination, string $name, ?int $userId): void
    {
        if (!is_dir(storage_path('plugins'))) {
            mkdir(storage_path('plugins'), 0755, true);
        }
        $this->markForRollback($destination);
        if (is_link($destination)) {
            unlink($destination);
        } elseif (is_dir($destination)) {
            $this->filesystem->deleteDirectory($destination, $name, $userId, $this->log);
        }
        $this->log->running('Copying plugin from local path...', $name, $userId);
        $this->filesystem->copyDirectory($source, $destination, $name, $userId, $this->log);
    }

    private function markForRollback(string $path): void
    {
        if (!is_dir($path) && !is_link($path)) {
            $this->incompleteInstallPath = $path;
        }
    }

    private function removeAssets(string $path, string $name, ?int $userId): void
    {
        if (!\ProcessMaker\Support\PluginVendorAssets::isEnabled()) {
            return;
        }

        try {
            app(TenantPluginAssetPublisher::class)->removePublishedPlugin($path);
        } catch (Throwable $exception) {
            $this->log->error('Could not remove tenant plugin assets: ' . $exception->getMessage(), $name, $userId);
        }
    }
}
