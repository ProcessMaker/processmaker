<?php

namespace ProcessMaker\Managers;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use function Illuminate\Support\php_binary;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Exception\PluginFilesystemException;
use ProcessMaker\Exception\PluginInstallException;
use ProcessMaker\Exception\PluginNotFoundException;
use ProcessMaker\Exception\PluginReinstallException;
use ProcessMaker\Exception\PluginToggleException;
use ProcessMaker\Models\Plugin;
use ProcessMaker\Services\PluginRuntimeReload;
use ProcessMaker\Support\PluginVendorAssets;
use RuntimeException;
use Throwable;

/**
 * Manager for handling plugin installation, uninstallation, and listing.
 */
class PluginManager
{
    /**
     * Plugin directory created by the current install attempt.
     * Removed if that attempt fails before setup finishes.
     */
    private ?string $incompleteInstallPath = null;

    private const HTTPS_SCHEME_PREFIX = 'https://';

    /**
     * Install a plugin from a GitHub repository or local path.
     *
     * @param string $repoUrl
     * @param string|null $branch
     * @param string|null $tag
     * @param int|null $userId
     * @return void
     * @throws PluginInstallException
     * @throws RuntimeException
     */
    public function install(
        string $repoUrl,
        ?string $branch = null,
        ?string $tag = null,
        ?int $userId = null,
        ?string $gitUsername = null,
        ?string $gitToken = null,
    ): void {
        if ($branch && $tag) {
            throw PluginInstallException::branchAndTagMutuallyExclusive();
        }

        $this->incompleteInstallPath = null;

        try {
            $this->installPluginFiles($repoUrl, $branch, $tag, $userId, $gitUsername, $gitToken);
        } catch (Throwable $exception) {
            $this->revertFailedInstall($userId);
            throw $exception;
        }
    }

    /**
     * Copy or clone the plugin, then run setup.
     *
     * @throws RuntimeException
     */
    private function installPluginFiles(string $repoUrl, ?string $branch, ?string $tag, ?int $userId, ?string $gitUsername, ?string $gitToken): void
    {
        $localPath = config('plugin_manager.allow_local_install_path', false)
            ? $this->resolveLocalPath($repoUrl)
            : null;

        if ($localPath !== null) {
            [$pluginPath, $repoName] = $this->installPluginFilesFromLocalPath($localPath, $userId);
        } else {
            [$pluginPath, $repoName] = $this->installPluginFilesFromRepository(
                $repoUrl,
                $branch,
                $tag,
                $userId,
                $gitUsername,
                $gitToken,
            );
        }

        $this->finishPluginSetup($pluginPath, $repoName, $userId);

        $this->logDone('Plugin installed successfully', $repoName, $userId);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function installPluginFilesFromLocalPath(string $localPath, ?int $userId): array
    {
        $repoName = basename($localPath);
        $pluginPath = storage_path('plugins') . '/' . $repoName;

        $this->logRunning("Starting installation of plugin from local path: {$repoName}", $repoName, $userId);

        if ($this->isPluginAlreadyInstalledAtLocalTarget($localPath, $pluginPath)) {
            $this->logRunning('Plugin is already installed at the target path', $repoName, $userId);
        } else {
            $this->copyLocalPluginIntoStorage($localPath, $pluginPath, $repoName, $userId);
        }

        return [$pluginPath, $repoName];
    }

    private function isPluginAlreadyInstalledAtLocalTarget(string $localPath, string $pluginPath): bool
    {
        $resolvedLocalPath = realpath($localPath);
        $resolvedPluginPath = realpath($pluginPath);

        return $resolvedLocalPath
            && $resolvedPluginPath
            && $resolvedLocalPath === $resolvedPluginPath
            && !is_link($pluginPath);
    }

    private function copyLocalPluginIntoStorage(string $localPath, string $pluginPath, string $repoName, ?int $userId): void
    {
        if (!is_dir(storage_path('plugins'))) {
            mkdir(storage_path('plugins'), 0755, true);
        }

        $this->markNewInstallForRollback($pluginPath);

        if (is_link($pluginPath)) {
            if (!@unlink($pluginPath)) {
                throw PluginInstallException::failedToRemoveExistingSymlink($pluginPath);
            }
        } elseif (is_dir($pluginPath)) {
            $this->logRunning('Removing existing plugin directory...', $repoName, $userId);
            $this->deleteDirectory($pluginPath, $repoName, $userId);
        }

        $this->logRunning('Copying plugin from local path...', $repoName, $userId);
        $this->copyDirectory($localPath, $pluginPath, $repoName, $userId);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function installPluginFilesFromRepository(
        string $repoUrl,
        ?string $branch,
        ?string $tag,
        ?int $userId,
        ?string $gitUsername,
        ?string $gitToken,
    ): array {
        $repoName = $this->extractRepoName($repoUrl);
        $pluginPath = storage_path('plugins') . '/' . $repoName;
        $authenticatedRepoUrl = $this->prepareGitUrl($repoUrl, $gitUsername, $gitToken);

        $this->logRunning("Starting installation of plugin: {$repoName}", $repoName, $userId);

        if (is_dir($pluginPath)) {
            $this->updateExistingRepository(
                $pluginPath,
                $repoName,
                $branch,
                $tag,
                $userId,
                $repoUrl,
                $authenticatedRepoUrl
            );
        } else {
            $this->cloneRepositoryForInstall(
                $authenticatedRepoUrl,
                $pluginPath,
                $repoName,
                $userId,
                $repoUrl,
                $branch,
                $tag,
            );
        }

        return [$pluginPath, $repoName];
    }

    private function cloneRepositoryForInstall(
        string $authenticatedRepoUrl,
        string $pluginPath,
        string $repoName,
        ?int $userId,
        string $repoUrl,
        ?string $branch,
        ?string $tag,
    ): void {
        $this->logRunning('Cloning repository...', $repoName, $userId);
        $this->markNewInstallForRollback($pluginPath);
        $this->cloneRepository($authenticatedRepoUrl, $pluginPath, $repoName, $userId, $repoUrl);

        if ($branch) {
            $this->logRunning("Checking out branch: {$branch}", $repoName, $userId);
            $this->checkoutBranch($pluginPath, $branch, $repoName, $userId);
        } elseif ($tag) {
            $this->logRunning("Checking out tag: {$tag}", $repoName, $userId);
            $this->checkoutTag($pluginPath, $tag, $repoName, $userId);
        }
    }

    /**
     * Pull latest changes on the plugin's current git branch and re-run setup.
     *
     * @throws PluginReinstallException
     * @throws RuntimeException
     */
    public function reinstall(string $pluginName, ?int $userId = null): void
    {
        $pluginPath = $this->resolveInstalledPluginPath($pluginName);

        if (!is_dir($pluginPath . '/.git')) {
            throw PluginReinstallException::notGitRepositoryFromRemote($pluginName);
        }

        $branch = $this->resolveCurrentBranch($pluginPath);
        if ($branch === null) {
            throw PluginReinstallException::notOnNamedBranch();
        }

        $this->logRunning("Starting reinstall of plugin: {$pluginName} on branch {$branch}", $pluginName, $userId);
        $this->updateExistingRepository($pluginPath, $pluginName, $branch, null, $userId);
        $this->finishPluginSetup($pluginPath, $pluginName, $userId);
        $this->logDone('Plugin reinstalled successfully', $pluginName, $userId);
    }

    /**
     * Uninstall a plugin.
     *
     * @param string $pluginName
     * @return void
     * @throws RuntimeException
     */
    public function uninstall(string $pluginName, ?int $userId = null): void
    {
        $pluginDir = storage_path('plugins');
        $pluginPath = "{$pluginDir}/{$pluginName}";
        $pluginDisabledPath = "{$pluginDir}/_{$pluginName}";

        if (is_dir($pluginDisabledPath) || is_link($pluginDisabledPath)) {
            $pluginPath = $pluginDisabledPath;
        } elseif (!is_dir($pluginPath) && !is_link($pluginPath)) {
            throw PluginNotFoundException::forPlugin($pluginName);
        }

        $this->logRunning("Starting uninstallation of plugin: {$pluginName}", $pluginName, $userId);

        // Run plugin uninstall command if it exists
        $uninstallCommand = "{$pluginName}:uninstall";
        $this->logRunning('Running plugin uninstall command...', $pluginName, $userId);
        $this->runCommand($uninstallCommand, $pluginName, $userId);

        $this->removeTenantPluginAssetsIfApplicable($pluginPath, $pluginName, $userId);

        $this->logRunning('Removing plugin directory...', $pluginName, $userId);
        $this->removePluginPath($pluginPath, $pluginName, $userId);

        $this->refreshRuntimeAfterPluginChange($pluginName, $userId);

        $this->logDone('Plugin uninstalled successfully', $pluginName, $userId);
    }

    private function runCommand(string $command, string $pluginName, ?int $userId = null): void
    {
        // Use absolute path: web SAPI (php-fpm) cwd is not the app root, so plain "artisan" fails.
        $artisan = base_path('artisan');
        $result = Process::env($this->artisanEnvironment())
            ->timeout(300)
            ->run(array_filter([
                php_binary(),
                $artisan,
                $command,
            ]));

        if (!$result->successful()) {
            if (str_contains($result->output(), 'is not defined')) {
                $this->logRunning("Plugin does not have a {$command} command", $pluginName, $userId);
            } else {
                $this->logError("Plugin {$command} command failed. Got output:\n\n{$result->output()}", $pluginName, $userId);
            }
        } else {
            $this->logRunning("Plugin {$command} command output:\n\n{$result->output()}", $pluginName, $userId);
        }
    }

    /**
     * Refresh routes and queue workers after plugin code changes.
     */
    private function refreshRuntimeAfterPluginChange(string $pluginName, ?int $userId = null): void
    {
        $this->rebuildRouteCache($pluginName, $userId);
        $this->publishRuntimeReload($pluginName, $userId);
    }

    /**
     * Rebuild the tenant's route cache in a fresh artisan process.
     *
     * The current request already booted without the plugin's providers, so
     * Artisan::call('route:cache') would persist stale routes. A subprocess
     * with TENANT set loads plugins from the tenant storage path and writes
     * the cache for that tenant.
     */
    private function rebuildRouteCache(string $pluginName, ?int $userId = null): void
    {
        $this->logRunning('Rebuilding route cache...', $pluginName, $userId);
        $this->runCommand('route:cache', $pluginName, $userId);
        $this->reloadCachedRoutes();
    }

    private function publishRuntimeReload(string $pluginName, ?int $userId = null): void
    {
        $this->logRunning('Publishing plugin runtime reload...', $pluginName, $userId);

        try {
            $version = app(PluginRuntimeReload::class)->publish($pluginName);
        } catch (Throwable $exception) {
            $this->logError(
                'Could not publish the plugin runtime reload: ' . $exception->getMessage(),
                $pluginName,
                $userId
            );

            return;
        }

        $this->logRunning(
            'Plugin runtime reload published (version ' . $version . '). Each pod restarts its own supervisor programs.',
            $pluginName,
            $userId
        );
    }

    /**
     * Environment variables for artisan subprocesses started from a web request.
     */
    private function artisanEnvironment(): array
    {
        $environment = [];
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (config('app.multitenancy') && $tenant) {
            $environment['TENANT'] = (string) $tenant->id;
        }

        return $environment;
    }

    /**
     * Load the freshly written route cache into the current process.
     *
     * Needed for Octane workers that keep the router in memory after
     * `route:cache` finishes in a subprocess.
     */
    private function reloadCachedRoutes(): void
    {
        $cachedRoutesPath = app()->getCachedRoutesPath();
        if (!is_file($cachedRoutesPath)) {
            return;
        }

        require $cachedRoutesPath;
    }

    /**
     * Install a plugin from a zip file path.
     *
     * @param string $zipPath Full path to the uploaded zip file
     * @return void
     * @throws RuntimeException
     */
    public function installFromZip(string $zipPath, ?int $userId = null): void
    {
        if (!file_exists($zipPath)) {
            throw PluginInstallException::zipFileNotFound($zipPath);
        }

        $zip = new \ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw PluginInstallException::failedToOpenZipFile($zipPath, $openResult);
        }

        $tmpExtractDir = sys_get_temp_dir() . '/pm-plugin-' . uniqid();

        if (!mkdir($tmpExtractDir, 0755, true)) {
            throw PluginInstallException::failedToCreateTempDirectory($tmpExtractDir);
        }

        $this->incompleteInstallPath = null;

        try {
            $zip->extractTo($tmpExtractDir);
            $zip->close();

            // Find the actual plugin directory (zip may have a top-level folder)
            $entries = array_diff(scandir($tmpExtractDir), ['.', '..']);
            $pluginSourceDir = $tmpExtractDir;

            if (count($entries) === 1) {
                $singleEntry = $tmpExtractDir . '/' . reset($entries);
                if (is_dir($singleEntry)) {
                    $pluginSourceDir = $singleEntry;
                }
            }

            // Validate before copying
            $repoName = basename($pluginSourceDir);
            $this->validatePlugin($pluginSourceDir, $repoName, $userId);

            $pluginPath = storage_path('plugins') . '/' . $repoName;

            $this->logRunning("Installing plugin from zip: {$repoName}", $repoName, $userId);
            // Ensure plugins directory exists
            if (!is_dir(storage_path('plugins'))) {
                mkdir(storage_path('plugins'), 0755, true);
            }

            $this->markNewInstallForRollback($pluginPath);

            // Remove existing plugin directory if present
            if (is_dir($pluginPath)) {
                $this->deleteDirectory($pluginPath, $repoName, $userId);
            }

            // Copy extracted directory to plugins folder
            $this->copyDirectory($pluginSourceDir, $pluginPath, $repoName, $userId);

            $this->finishPluginSetup($pluginPath, $repoName, $userId);

            $this->logDone('Plugin installed successfully', $repoName, $userId);
        } catch (Throwable $exception) {
            $this->revertFailedInstall($userId);
            throw $exception;
        } finally {
            // Always clean up temp directory and zip file
            if (is_dir($tmpExtractDir)) {
                $this->deleteDirectory($tmpExtractDir, $repoName, $userId);
            }
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    /**
     * Toggle a plugin enabled/disabled by renaming its directory.
     * Disabled plugins have a `_` prefix on their folder name.
     *
     * @param string $pluginName
     * @return bool True if now enabled, false if now disabled
     * @throws RuntimeException
     */
    public function toggle(string $pluginName, ?int $userId = null): bool
    {
        $pluginsFolder = storage_path('plugins');

        // Check if currently enabled (no underscore prefix)
        $enabledPath = $pluginsFolder . '/' . $pluginName;
        $disabledName = '_' . ltrim($pluginName, '_');
        $disabledPath = $pluginsFolder . '/' . $disabledName;

        if (is_dir($enabledPath)) {
            // Disable it
            if (!rename($enabledPath, $disabledPath)) {
                $this->logError("Failed to disable plugin: {$pluginName}", $pluginName, $userId);
                throw PluginToggleException::failedToDisable($pluginName);
            }

            $this->refreshRuntimeAfterPluginChange($pluginName, $userId);

            return false;
        }

        if (is_dir($disabledPath)) {
            // Enable it
            $enabledName = ltrim($pluginName, '_');
            $enabledPath = $pluginsFolder . '/' . $enabledName;
            if (!rename($disabledPath, $enabledPath)) {
                $this->logError("Failed to enable plugin: {$pluginName}", $pluginName, $userId);
                throw PluginToggleException::failedToEnable($pluginName);
            }

            $this->refreshRuntimeAfterPluginChange($enabledName, $userId);

            return true;
        }

        $this->logError("Plugin not found: {$pluginName}", $pluginName, $userId);
        throw PluginNotFoundException::forPlugin($pluginName);
    }

    /**
     * List all installed plugins, including disabled ones.
     *
     * @return array
     */
    public function list(): array
    {
        $pluginsFolder = storage_path('plugins');
        $plugins = [];

        if (!is_dir($pluginsFolder)) {
            return $plugins;
        }

        $pluginPaths = glob($pluginsFolder . '/*', GLOB_ONLYDIR);

        foreach ($pluginPaths as $pluginPath) {
            $dirName = basename($pluginPath);
            $isDisabled = str_starts_with($dirName, '_');

            try {
                $plugin = Plugin::fromPath($pluginPath);
                $reference = $plugin->getReference();
                $composerJson = $plugin->getComposerJson();

                $plugins[] = [
                    'name' => $isDisabled ? ltrim($dirName, '_') : $dirName,
                    'url' => $this->stripEmbeddedCredentialsFromHttpUrl($plugin->getUrl()),
                    'folder' => $dirName,
                    'description' => $plugin->getDescription() ?? 'No description',
                    'version' => $composerJson['version'] ?? null,
                    'branch' => $reference,
                    'source' => $plugin->isGitRepository() ? 'git' : 'filesystem',
                    'enabled' => !$isDisabled ? 'Enabled' : 'Disabled',
                ];
            } catch (\Exception $e) {
                // Skip invalid plugins
                continue;
            }
        }

        return $plugins;
    }

    /**
     * Extract repository name from URL.
     *
     * @param string $repoUrl
     * @return string
     */
    protected function extractRepoName(string $repoUrl): string
    {
        // Remove only a single trailing '.git' or '/' if present
        $repoUrl = preg_replace('/(\.git|\/)$/', '', $repoUrl);

        // Extract name from URL
        if (preg_match('#/([^/]+)$#', $repoUrl, $matches)) {
            return $matches[1];
        }

        throw PluginInstallException::cannotExtractRepositoryNameFromUrl($repoUrl);
    }

    /**
     * Clone a repository.
     *
     * @param string $repoUrl
     * @param string $destination
     * @return void
     * @throws RuntimeException
     */
    protected function cloneRepository(
        string $repoUrl,
        string $destination,
        string $repoName,
        ?int $userId = null,
        ?string $canonicalRepoUrl = null,
    ): void {
        try {
            $process = Process::timeout(3600)->run(['git', 'clone', $repoUrl, $destination]);
        } catch (ProcessTimedOutException) {
            throw PluginInstallException::gitCloneTimedOut($repoName);
        }

        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to clone repository: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToCloneRepository($stderr);
        }

        $cleanOrigin = $this->stripEmbeddedCredentialsFromHttpUrl($canonicalRepoUrl ?? $repoUrl);
        Process::path($destination)->timeout(60)->run(['git', 'remote', 'set-url', 'origin', $cleanOrigin]);
    }

    protected function finishPluginSetup(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        $this->logRunning('Validating plugin...', $repoName, $userId);
        $this->validatePlugin($pluginPath, $repoName, $userId);

        $this->logRunning('Installing dependencies...', $repoName, $userId);
        $this->runComposerInstall($pluginPath, $repoName, $userId);

        $installCommand = "{$repoName}:install";
        $this->logRunning('installCommand: ' . $installCommand, $repoName, $userId);
        $this->logRunning('Running plugin install command...', $repoName, $userId);
        $this->runCommand($installCommand, $repoName, $userId);

        $this->publishTenantPluginAssetsIfApplicable($pluginPath, $repoName, $userId);

        // Setup finished. Keep the directory even if a later route refresh fails.
        $this->incompleteInstallPath = null;

        $this->refreshRuntimeAfterPluginChange($repoName, $userId);
    }

    /**
     * Copy the plugin's public/ assets onto the current tenant's public disk.
     * Failures are logged and do not fail the install.
     */
    protected function publishTenantPluginAssetsIfApplicable(string $pluginPath, string $pluginName, ?int $userId = null): void
    {
        if (!PluginVendorAssets::isEnabled()) {
            return;
        }

        try {
            $published = app(TenantPluginAssetPublisher::class)->publishPlugin($pluginPath);
            $this->logRunning(
                $published ? 'Tenant plugin public assets published.' : 'Tenant plugin assets skipped: no public directory.',
                $pluginName,
                $userId
            );
        } catch (Throwable $exception) {
            $this->logError('Could not publish tenant plugin assets: ' . $exception->getMessage(), $pluginName, $userId);
        }
    }

    /**
     * Remove the plugin's copy from the current tenant's public disk before uninstall.
     */
    protected function removeTenantPluginAssetsIfApplicable(string $pluginPath, string $pluginName, ?int $userId = null): void
    {
        if (!PluginVendorAssets::isEnabled()) {
            return;
        }

        try {
            app(TenantPluginAssetPublisher::class)->removePublishedPlugin($pluginPath);
        } catch (Throwable $exception) {
            $this->logError('Could not remove tenant plugin assets: ' . $exception->getMessage(), $pluginName, $userId);
        }
    }

    /**
     * Remember a plugin path that did not exist before this install attempt.
     */
    private function markNewInstallForRollback(string $pluginPath): void
    {
        if (is_dir($pluginPath) || is_link($pluginPath)) {
            return;
        }

        $this->incompleteInstallPath = $pluginPath;
    }

    /**
     * Remove a plugin directory left behind by a failed install.
     */
    public function revertFailedInstall(?int $userId = null): void
    {
        $pluginPath = $this->incompleteInstallPath;
        $this->incompleteInstallPath = null;

        if ($pluginPath === null || (!is_dir($pluginPath) && !is_link($pluginPath))) {
            return;
        }

        $pluginName = basename($pluginPath);
        $this->logRunning(
            'Installation failed. Removing the incomplete plugin directory.',
            $pluginName,
            $userId
        );

        try {
            $this->removePluginPath($pluginPath, $pluginName, $userId);
        } catch (Throwable $cleanupException) {
            $this->logError(
                'Could not remove the incomplete plugin directory: ' . $cleanupException->getMessage(),
                $pluginName,
                $userId
            );
        }
    }

    protected function resolveInstalledPluginPath(string $pluginName): string
    {
        $pluginDir = storage_path('plugins');
        $pluginPath = "{$pluginDir}/{$pluginName}";
        $pluginDisabledPath = "{$pluginDir}/_{$pluginName}";

        if (is_dir($pluginDisabledPath) || is_link($pluginDisabledPath)) {
            return $pluginDisabledPath;
        }

        if (is_dir($pluginPath) || is_link($pluginPath)) {
            return $pluginPath;
        }

        throw PluginNotFoundException::forPlugin($pluginName);
    }

    protected function resolveCurrentBranch(string $pluginPath): ?string
    {
        $process = Process::path($pluginPath)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);

        if (!$process->successful()) {
            return null;
        }

        $branch = trim($process->output());

        if ($branch === '' || $branch === 'HEAD') {
            return null;
        }

        return $branch;
    }

    /**
     * Fetch remotes and check out the requested ref when the plugin clone already exists.
     */
    protected function updateExistingRepository(
        string $pluginPath,
        string $repoName,
        ?string $branch,
        ?string $tag,
        ?int $userId = null,
        ?string $canonicalRepoUrl = null,
        ?string $authenticatedRepoUrl = null,
    ): void {
        $this->logRunning('Plugin directory exists, fetching from remote...', $repoName, $userId);

        $restoreOriginUrl = $canonicalRepoUrl !== null
            ? $this->stripEmbeddedCredentialsFromHttpUrl($canonicalRepoUrl)
            : null;

        $previousOriginProcess = Process::path($pluginPath)->timeout(60)->run([
            'git', 'remote', 'get-url', 'origin',
        ]);
        $previousOrigin = $previousOriginProcess->successful()
            ? trim($previousOriginProcess->output())
            : '';

        $temporaryAuth = $authenticatedRepoUrl !== null
            && $restoreOriginUrl !== null
            && str_starts_with($authenticatedRepoUrl, self::HTTPS_SCHEME_PREFIX);

        if ($temporaryAuth) {
            Process::path($pluginPath)->timeout(60)->run([
                'git', 'remote', 'set-url', 'origin', $authenticatedRepoUrl,
            ]);
        }

        try {
            $this->fetchRepository($pluginPath, $repoName, $userId);

            if ($tag) {
                $this->logRunning("Checking out tag: {$tag}", $repoName, $userId);
                $this->checkoutTag($pluginPath, $tag, $repoName, $userId);

                return;
            }

            $branchToCheckout = $branch ?? $this->resolveRemoteDefaultBranch($pluginPath, $repoName, $userId);
            $this->logRunning("Checking out branch: {$branchToCheckout}", $repoName, $userId);
            $this->checkoutBranch($pluginPath, $branchToCheckout, $repoName, $userId);
            $this->logRunning("Pulling latest changes for branch {$branchToCheckout}...", $repoName, $userId);
            $this->pullRepository($pluginPath, $repoName, $userId, $branchToCheckout);
        } finally {
            $cleanOrigin = $restoreOriginUrl ?? '';
            if ($cleanOrigin === '' && $previousOrigin !== '') {
                $cleanOrigin = $this->stripEmbeddedCredentialsFromHttpUrl($previousOrigin);
            }

            if ($cleanOrigin !== '' && is_dir($pluginPath . '/.git')) {
                Process::path($pluginPath)->timeout(60)->run([
                    'git', 'remote', 'set-url', 'origin', $cleanOrigin,
                ]);
            }
        }
    }

    protected function fetchRepository(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        $process = Process::path($pluginPath)->timeout(300)->run(['git', 'fetch', '--all', '--tags']);

        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to fetch repository: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToFetchRepository($stderr);
        }
    }

    protected function resolveRemoteDefaultBranch(string $pluginPath, string $repoName, ?int $userId = null): string
    {
        $process = Process::path($pluginPath)->timeout(60)->run(['git', 'rev-parse', '--abbrev-ref', 'origin/HEAD']);

        if ($process->successful()) {
            $ref = trim($process->output());
            if (str_starts_with($ref, 'origin/')) {
                return substr($ref, strlen('origin/'));
            }
            if ($ref !== '' && $ref !== 'HEAD') {
                return $ref;
            }
        }

        foreach (['main', 'master', 'develop'] as $fallbackBranch) {
            $check = Process::path($pluginPath)->run([
                'git',
                'show-ref',
                '--verify',
                '--quiet',
                "refs/remotes/origin/{$fallbackBranch}",
            ]);
            if ($check->successful()) {
                return $fallbackBranch;
            }
        }

        $this->logError('Could not determine default branch for plugin repository', $repoName, $userId);
        throw PluginInstallException::cannotDetermineDefaultBranch();
    }

    protected function pullRepository(string $pluginPath, string $repoName, ?int $userId = null, ?string $branch = null): void
    {
        $command = $branch === null
            ? ['git', 'pull']
            : ['git', 'pull', 'origin', $branch];

        $process = Process::path($pluginPath)->timeout(300)->run($command);

        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to pull repository: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToPullRepository($stderr);
        }
    }

    /**
     * Checkout a branch.
     *
     * @param string $pluginPath
     * @param string $branch
     * @return void
     * @throws RuntimeException
     */
    protected function checkoutBranch(string $pluginPath, string $branch, string $repoName, ?int $userId = null): void
    {
        $process = Process::path($pluginPath)->timeout(120)->run(['git', 'checkout', $branch]);

        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to checkout branch: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToCheckoutBranch($branch, $stderr);
        }
    }

    /**
     * Checkout a tag.
     *
     * @param string $pluginPath
     * @param string $tag
     * @return void
     * @throws RuntimeException
     */
    protected function checkoutTag(string $pluginPath, string $tag, string $repoName, ?int $userId = null): void
    {
        $process = Process::path($pluginPath)->timeout(120)->run(['git', 'checkout', "tags/{$tag}"]);

        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to checkout tag: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToCheckoutTag($tag, $stderr);
        }
    }

    /**
     * Validate that the plugin has a valid composer.json structure.
     *
     * @param string $pluginPath
     * @return void
     * @throws RuntimeException
     */
    protected function validatePlugin(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        $composerJsonPath = $pluginPath . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            $this->logError('composer.json not found in plugin: ' . $pluginPath, $repoName, $userId);
            throw PluginInstallException::composerJsonNotFound($pluginPath);
        }

        $content = file_get_contents($composerJsonPath);
        $composerJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError('Invalid JSON in composer.json at ' . $composerJsonPath . ': ' . json_last_error_msg(), $repoName, $userId);
            throw PluginInstallException::invalidComposerJson($composerJsonPath, json_last_error_msg());
        }

        // Check if require or require-dev is an empty array ([]), if so change for empty object and save to file
        $changed = false;
        if (Arr::get($composerJson, 'require', []) === []) {
            $composerJson['require'] = new \stdClass();
            $changed = true;
        }
        if (Arr::get($composerJson, 'require-dev', []) === []) {
            $composerJson['require-dev'] = new \stdClass();
            $changed = true;
        }
        if ($changed) {
            file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT));
        }

        // Check PSR-4 autoload namespace
        $psr4Namespaces = Arr::get($composerJson, 'autoload.psr-4', []);

        if (empty($psr4Namespaces)) {
            $this->logError('Plugin must have PSR-4 autoload configuration in composer.json', $repoName, $userId);
            throw PluginInstallException::missingPsr4Autoload();
        }

        $valid = false;
        foreach ($psr4Namespaces as $namespace => $path) {
            $prefixes = [
                'ProcessMaker\\Plugins\\',
                'ProcessMaker\\Package\\',
                'ProcessMaker\\Packages\\',
            ];
            $valid = (bool) collect($prefixes)->first(fn ($prefix) => str_starts_with($namespace, $prefix));
            if ($valid) {
                break;
            }
        }

        if (!$valid) {
            $this->logError('Plugin PSR-4 namespace must start with ' . implode(', ', $prefixes) . '. Found: ' . implode(', ', array_keys($psr4Namespaces)), $repoName, $userId);
            throw PluginInstallException::invalidPsr4Namespace(implode(', ', array_keys($psr4Namespaces)));
        }
    }

    /**
     * Fetch branches from the repository.
     *
     * @param string $pluginPath
     * @return void
     * @throws RuntimeException
     */
    protected function fetchBranches(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        $process = Process::path($pluginPath)->timeout(60)->run(['git', 'branch', '-a']);
        if (!$process->successful()) {
            $stderr = self::redactHttpUserInfoFromString($process->errorOutput());
            $this->logError('Failed to fetch branches: ' . $stderr, $repoName, $userId);
            throw PluginInstallException::failedToFetchBranches($stderr);
        }
    }

    /**
     * Run composer install in the plugin directory.
     *
     * @param string $pluginPath
     * @return void
     * @throws RuntimeException
     */
    protected function runComposerInstall(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        set_time_limit(0);
        $process = Process::path($pluginPath)
            ->forever()
            ->env([
                'PATH' => '/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:' . (getenv('PATH') ?: ''),
            ])
            ->run(['composer', 'install', '--no-interaction']);

        if (!$process->successful()) {
            $this->logError('Failed to run composer install: ' . $process->errorOutput(), $repoName, $userId);
            throw PluginInstallException::failedToRunComposerInstall($process->errorOutput());
        }
    }

    /**
     * Copy a directory recursively.
     *
     * @param string $source
     * @param string $destination
     * @return void
     * @throws RuntimeException
     */
    protected function copyDirectory(string $source, string $destination, string $repoName, ?int $userId = null): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = array_diff(scandir($source), ['.', '..']);

        foreach ($files as $file) {
            $srcPath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath, $repoName, $userId);
            } else {
                if (!copy($srcPath, $destPath)) {
                    $this->logError('Failed to copy file: ' . $srcPath . ' to ' . $destPath, $repoName, $userId);
                    throw PluginInstallException::failedToCopyFile($srcPath, $destPath);
                }
            }
        }
    }

    /**
     * Remove a plugin from storage/plugins without deleting symlink targets.
     *
     * @throws RuntimeException
     */
    protected function removePluginPath(string $pluginPath, string $pluginName, ?int $userId = null): void
    {
        if (is_link($pluginPath)) {
            $this->logRunning('Removing plugin symlink (source directory is kept)...', $pluginName, $userId);
            if (!@unlink($pluginPath)) {
                $this->logError('Failed to remove plugin symlink: ' . $pluginPath, $pluginName, $userId);
                throw PluginFilesystemException::failedToRemovePluginSymlink($pluginPath);
            }

            return;
        }

        $this->deleteDirectory($pluginPath, $pluginName, $userId);
    }

    /**
     * Delete a directory recursively.
     *
     * @param string $directory
     * @return void
     * @throws RuntimeException
     */
    protected function deleteDirectory(string $directory, string $repoName, ?int $userId = null): void
    {
        if ($this->unlinkSymlinkForDelete($directory, $repoName, $userId)) {
            return;
        }

        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory), ['.', '..']) as $file) {
            $this->deleteDirectoryChild($directory . '/' . $file, $repoName, $userId);
        }

        $this->removeEmptyDirectory($directory, $repoName, $userId);
    }

    private function unlinkSymlinkForDelete(string $directory, string $repoName, ?int $userId): bool
    {
        if (!is_link($directory)) {
            return false;
        }

        if (!@unlink($directory)) {
            $this->logError('Failed to remove symlink: ' . $directory, $repoName, $userId);
            throw PluginFilesystemException::failedToRemoveSymlink($directory);
        }

        return true;
    }

    private function deleteDirectoryChild(string $path, string $repoName, ?int $userId): void
    {
        if (is_dir($path) && !is_link($path)) {
            $this->deleteDirectory($path, $repoName, $userId);

            return;
        }

        if (!@unlink($path) && file_exists($path)) {
            $this->logError('Failed to delete file: ' . $path, $repoName, $userId);
            throw PluginFilesystemException::failedToDeleteFile($path);
        }
    }

    private function removeEmptyDirectory(string $directory, string $repoName, ?int $userId): void
    {
        clearstatcache(true, $directory);

        if (count(array_diff(scandir($directory), ['.', '..'])) !== 0) {
            $this->logError('Directory not empty after attempting to delete: ' . $directory, $repoName, $userId);
            throw PluginFilesystemException::directoryNotEmptyAfterDelete($directory);
        }

        if (!@rmdir($directory) && is_dir($directory)) {
            $this->logError('Failed to delete directory: ' . $directory, $repoName, $userId);
            throw PluginFilesystemException::failedToDeleteDirectory($directory);
        }
    }

    /**
     * Resolve and check if the given path is a valid local directory.
     *
     * @param string $path
     * @return string|null Returns the resolved absolute path if it exists, null otherwise
     */
    protected function resolveLocalPath(string $path): ?string
    {
        $resolvedPath = null;

        foreach ([$path, base_path($path)] as $candidate) {
            $resolvedPath = $this->resolveExistingDirectoryPath($candidate);
            if ($resolvedPath !== null) {
                break;
            }
        }

        return $resolvedPath;
    }

    private function resolveExistingDirectoryPath(string $path): ?string
    {
        if (is_dir($path)) {
            $resolved = realpath($path);

            return $resolved !== false ? $resolved : null;
        }

        $resolved = realpath($path);

        return ($resolved !== false && is_dir($resolved)) ? $resolved : null;
    }

    /**
     * Prepare HTTPS git URL: optional UI username/password, else GITHUB_TOKEN for github.com.
     */
    protected function prepareGitUrl(
        string $url,
        ?string $username = null,
        ?string $token = null,
    ): string {
        if (!str_starts_with($url, self::HTTPS_SCHEME_PREFIX)) {
            return $url;
        }

        if ($this->hasExplicitGitCredentials($username, $token)) {
            return $this->embedExplicitGitCredentialsInHttpsUrl($url, $username, $token);
        }

        return $this->embedGithubEnvTokenInUrl($url);
    }

    private function hasExplicitGitCredentials(?string $username, ?string $token): bool
    {
        return ($username !== null && trim($username) !== '')
            || ($token !== null && $token !== '');
    }

    private function embedExplicitGitCredentialsInHttpsUrl(
        string $url,
        ?string $username,
        ?string $token,
    ): string {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $hasToken = $token !== null && $token !== '';
        $user = rawurlencode((string) $username);
        $pass = $hasToken ? rawurlencode((string) $token) : '';
        $auth = $pass !== '' ? "{$user}:{$pass}@" : "{$user}@";

        return $this->formatHttpsUrlFromParts($parts, $auth);
    }

    private function embedGithubEnvTokenInUrl(string $url): string
    {
        $token = env('GITHUB_TOKEN');
        if (!$token || !str_starts_with($url, 'https://github.com/')) {
            return $url;
        }

        return str_replace(
            'https://github.com/',
            self::HTTPS_SCHEME_PREFIX . "{$token}@github.com/",
            $url
        );
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function formatHttpsUrlFromParts(array $parts, string $userInfoPrefix = ''): string
    {
        $path = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return self::HTTPS_SCHEME_PREFIX . $userInfoPrefix . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . $path
            . $fragment;
    }

    /**
     * Remove user:pass from an HTTPS URL (for restoring git remote after authenticated fetch).
     */
    protected function stripEmbeddedCredentialsFromHttpUrl(string $url): string
    {
        if (!str_starts_with($url, self::HTTPS_SCHEME_PREFIX)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        return $this->formatHttpsUrlFromParts($parts);
    }

    /**
     * Remove embedded HTTP(S) credentials from log and error text (user:pass or token-only).
     */
    public static function redactHttpUserInfoFromString(string $message): string
    {
        $message = preg_replace('#://[^/\s:@]+:[^/\s@]+@#', '://', $message) ?? $message;
        $message = preg_replace('#://[^/\s@]+@#', '://', $message) ?? $message;

        return $message;
    }

    /**
     * Log a running status message.
     *
     * @param string $message
     * @param string $pluginName
     * @return void
     */
    protected function logRunning(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'running', $pluginName, $userId);
    }

    /**
     * Log an error status message.
     *
     * @param string $message
     * @param string $pluginName
     * @return void
     */
    protected function logError(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'error', $pluginName, $userId);
    }

    /**
     * Log a done status message.
     *
     * @param string $message
     * @param string $pluginName
     * @return void
     */
    protected function logDone(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'done', $pluginName, $userId);
    }

    /**
     * Log a plugin event.
     *
     * @param string $message
     * @param string $type
     * @param string $pluginName
     * @return void
     */
    protected function log(string $message, string $type, string $pluginName, ?int $userId = null): void
    {
        $message = self::redactHttpUserInfoFromString($message);

        if ($type === 'error') {
            \Log::error($message);
        } else {
            \Log::info($message);
        }

        if ($userId) {
            event(new PluginLog($message, $type, $pluginName, $userId));
        }
    }
}
