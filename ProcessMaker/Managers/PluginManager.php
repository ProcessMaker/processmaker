<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use function Illuminate\Support\php_binary;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Models\Plugin;
use RuntimeException;

/**
 * Manager for handling plugin installation, uninstallation, and listing.
 */
class PluginManager
{
    /**
     * Install a plugin from a GitHub repository or local path.
     *
     * @param string $repoUrl
     * @param string|null $branch
     * @param string|null $tag
     * @param int|null $userId
     * @return void
     * @throws RuntimeException
     */
    public function install(string $repoUrl, ?string $branch = null, ?string $tag = null, ?int $userId = null): void
    {
        if ($branch && $tag) {
            throw new RuntimeException('Cannot specify both --branch and --tag');
        }

        // Check if this is a local path
        $localPath = $this->resolveLocalPath($repoUrl);
        $isLocalPath = $localPath !== null;

        if ($isLocalPath) {
            // Extract plugin name from path
            $repoName = basename($localPath);
            $pluginPath = storage_path('plugins') . '/' . $repoName;

            $this->logRunning("Starting installation of plugin from local path: {$repoName}", $repoName, $userId);

            // Create symlink if target doesn't exist
            if (is_link($pluginPath) || is_dir($pluginPath)) {
                // throw new RuntimeException("Plugin directory or symlink already exists: {$pluginPath}");
                $this->logRunning("Plugin directory or symlink already exists: {$pluginPath}", $repoName, $userId);
            } else {
                $this->logRunning('Creating symlink to local path...', $repoName, $userId);
                $this->createSymlink($localPath, $pluginPath, $repoName, $userId);
            }
        } else {
            // Extract repository name from URL
            $repoName = $this->extractRepoName($repoUrl);
            $pluginPath = storage_path('plugins') . '/' . $repoName;

            $this->logRunning("Starting installation of plugin: {$repoName}", $repoName, $userId);

            // Clone or pull the repository
            if (is_dir($pluginPath)) {
                // Fetch branches
                $this->logRunning('Plugin directory exists, checking out main branch...', $repoName, $userId);
                $this->checkoutBranch($pluginPath, 'main', $repoName, $userId);
                $this->logRunning('Pulling latest changes from main branch...', $repoName, $userId);
                $this->pullRepository($pluginPath, $repoName, $userId);
            } else {
                $this->logRunning('Cloning repository...', $repoName, $userId);
                $this->cloneRepository($repoUrl, $pluginPath, $repoName, $userId);
            }

            // Checkout branch or tag if specified
            if ($branch) {
                $this->logRunning("Checking out branch: {$branch}", $repoName, $userId);
                $this->checkoutBranch($pluginPath, $branch, $repoName, $userId);
            } elseif ($tag) {
                $this->logRunning("Checking out tag: {$tag}", $repoName, $userId);
                $this->checkoutTag($pluginPath, $tag, $repoName, $userId);
            }
        }

        // Validate composer.json
        $this->logRunning('Validating plugin...', $repoName, $userId);
        $this->validatePlugin($pluginPath, $repoName, $userId);

        // Run composer install
        $this->logRunning('Installing dependencies...', $repoName, $userId);
        $this->runComposerInstall($pluginPath, $repoName, $userId);

        // Run plugin install command if it exists
        $installCommand = "{$repoName}:install";

        $this->logRunning('Running plugin install command...', $repoName, $userId);
        $this->runCommand($installCommand, $repoName, $userId);

        // Rebuild route cache so we pick up the new routes from the plugin
        $this->logRunning('Rebuilding route cache...', $repoName, $userId);
        $this->runCommand('route:cache', $repoName, $userId);

        $this->logDone('Plugin installed successfully', $repoName, $userId);
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

        if (is_dir($pluginDisabledPath)) {
            $pluginPath = $pluginDisabledPath;
        } elseif (!is_dir($pluginPath)) {
            throw new RuntimeException("Plugin not found: {$pluginName}");
        }

        $this->logRunning("Starting uninstallation of plugin: {$pluginName}", $pluginName, $userId);

        // Run plugin uninstall command if it exists
        $uninstallCommand = "{$pluginName}:uninstall";
        $this->logRunning('Running plugin uninstall command...', $pluginName, $userId);
        $this->runCommand($uninstallCommand, $pluginName, $userId);

        // Delete the plugin directory
        $this->logRunning('Removing plugin directory...', $pluginName, $userId);
        $this->deleteDirectory($pluginPath, $pluginName, $userId);

        // Rebuild route cache so we remove the routes from the plugin
        $this->logRunning('Rebuilding route cache...', $pluginName, $userId);
        $this->runCommand('route:cache', $pluginName);

        $this->logDone('Plugin uninstalled successfully', $pluginName, $userId);
    }

    private function runCommand(string $command, string $pluginName, ?int $userId = null): void
    {
        // Use absolute path: web SAPI (php-fpm) cwd is not the app root, so plain "artisan" fails.
        //TODO: Change artisan_binary() for base_path('artisan')
        $artisan = base_path('artisan');
        $result = Process::run(array_filter([
            php_binary(),
            $artisan,
            $command,
        ]));

        if (!$result->successful()) {
            if (str_contains($result->output(), 'is not defined')) {
                \Log::info("Plugin does not have a {$command} command", ['output' => $result->output()]);
                $this->logRunning("Plugin does not have a {$command} command", $pluginName, $userId);
            } else {
                \Log::info("Plugin {$command} command failed. Got output:\n\n{$result->output()}", ['output' => $result->output()]);
                $this->logError("Plugin {$command} command failed. Got output:\n\n{$result->output()}", $pluginName, $userId);
            }
        } else {
            \Log::info("Plugin {$command} command output:\n\n{$result->output()}", ['output' => $result->output()]);
            $this->logRunning("Plugin {$command} command output:\n\n{$result->output()}", $pluginName, $userId);
        }
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
            throw new RuntimeException("Zip file not found: {$zipPath}");
        }

        $zip = new \ZipArchive();
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw new RuntimeException("Failed to open zip file: {$zipPath} (error code: {$openResult})");
        }

        $tmpExtractDir = sys_get_temp_dir() . '/pm-plugin-' . uniqid();

        if (!mkdir($tmpExtractDir, 0755, true)) {
            throw new RuntimeException("Failed to create temp directory: {$tmpExtractDir}");
        }

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

            // Remove existing plugin directory if present
            if (is_dir($pluginPath)) {
                $this->deleteDirectory($pluginPath, $repoName, $userId);
            }

            // Copy extracted directory to plugins folder
            $this->copyDirectory($pluginSourceDir, $pluginPath, $repoName, $userId);

            // Run composer install
            $this->logRunning('Installing dependencies...', $repoName, $userId);
            $this->runComposerInstall($pluginPath, $repoName, $userId);

            // Run plugin install command if it exists
            $installCommand = "{$repoName}:install";
            $this->logRunning('Running plugin install command...', $repoName, $userId);
            $this->runCommand($installCommand, $repoName, $userId);

            // Rebuild route cache
            $this->logRunning('Rebuilding route cache...', $repoName, $userId);
            $this->runCommand('route:cache', $repoName, $userId);

            $this->logDone('Plugin installed successfully', $repoName, $userId);
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
                throw new RuntimeException("Failed to disable plugin: {$pluginName}");
            }

            return false;
        }

        if (is_dir($disabledPath)) {
            // Enable it
            $enabledName = ltrim($pluginName, '_');
            $enabledPath = $pluginsFolder . '/' . $enabledName;
            if (!rename($disabledPath, $enabledPath)) {
                $this->logError("Failed to enable plugin: {$pluginName}", $pluginName, $userId);
                throw new RuntimeException("Failed to enable plugin: {$pluginName}");
            }

            return true;
        }

        $this->logError("Plugin not found: {$pluginName}", $pluginName, $userId);
        throw new RuntimeException("Plugin not found: {$pluginName}");
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
                    'url' => $plugin->getUrl(),
                    'folder' => $dirName,
                    'description' => $plugin->getDescription() ?? 'No description',
                    'version' => $composerJson['version'] ?? null,
                    'branch' => $reference,
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

        throw new RuntimeException("Could not extract repository name from URL: {$repoUrl}");
    }

    /**
     * Clone a repository.
     *
     * @param string $repoUrl
     * @param string $destination
     * @return void
     * @throws RuntimeException
     */
    protected function cloneRepository(string $repoUrl, string $destination, string $repoName, ?int $userId = null): void
    {
        // Handle GITHUB_TOKEN if present
        $url = $this->prepareGitUrl($repoUrl);

        $process = Process::run("git clone {$url} {$destination}");

        if (!$process->successful()) {
            $this->logError('Failed to clone repository: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException('Failed to clone repository: ' . $process->errorOutput());
        }
    }

    /**
     * Pull latest changes from repository.
     *
     * @param string $pluginPath
     * @return void
     * @throws RuntimeException
     */
    protected function pullRepository(string $pluginPath, string $repoName, ?int $userId = null): void
    {
        $process = Process::path($pluginPath)->run('git pull');

        if (!$process->successful()) {
            $this->logError('Failed to pull repository: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException('Failed to pull repository: ' . $process->errorOutput());
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
        $process = Process::path($pluginPath)->run("git checkout {$branch}");

        if (!$process->successful()) {
            $this->logError('Failed to checkout branch: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException("Failed to checkout branch {$branch}: " . $process->errorOutput());
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
        $process = Process::path($pluginPath)->run("git checkout tags/{$tag}");

        if (!$process->successful()) {
            $this->logError('Failed to checkout tag: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException("Failed to checkout tag {$tag}: " . $process->errorOutput());
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
            throw new RuntimeException("composer.json not found in plugin: {$pluginPath}");
        }

        $content = file_get_contents($composerJsonPath);
        $composerJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError('Invalid JSON in composer.json at ' . $composerJsonPath . ': ' . json_last_error_msg(), $repoName, $userId);
            throw new RuntimeException(
                "Invalid JSON in composer.json at {$composerJsonPath}: " . json_last_error_msg()
            );
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
            throw new RuntimeException('Plugin must have PSR-4 autoload configuration in composer.json');
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
            // if (str_starts_with($namespace, 'ProcessMaker\\Plugins\\')) {
            //     $valid = true;
            //     break;
            // }
            // if (str_starts_with($namespace, 'ProcessMaker\\Package\\')) {
            //     $valid = true;
            //     break;
            // }
            // //TODO: Check if needs to be package or packages or both
            // if (str_starts_with($namespace, 'ProcessMaker\\Packages\\')) {
            //     $valid = true;
            //     break;
            // }
        }

        if (!$valid) {
            $this->logError('Plugin PSR-4 namespace must start with ' . implode(', ', $prefixes) . '. Found: ' . implode(', ', array_keys($psr4Namespaces)), $repoName, $userId);
            throw new RuntimeException(
                "Plugin PSR-4 namespace must start with 'ProcessMaker\\Plugins\\'. Found: " .
                implode(', ', array_keys($psr4Namespaces))
            );
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
        $process = Process::path($pluginPath)->run('git branch -a');
        if (!$process->successful()) {
            $this->logError('Failed to fetch branches: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException('Failed to fetch branches: ' . $process->errorOutput());
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
        //TODO: Check if needs to be export PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin
        $process = Process::path($pluginPath)->run('export PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin && composer install --no-interaction');

        if (!$process->successful()) {
            $this->logError('Failed to run composer install: ' . $process->errorOutput(), $repoName, $userId);
            throw new RuntimeException('Failed to run composer install: ' . $process->errorOutput());
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
                    throw new RuntimeException("Failed to copy file: {$srcPath} to {$destPath}");
                }
            }
        }
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
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path) && !is_link($path)) {
                $this->deleteDirectory($path, $repoName, $userId);
            } else {
                if (!@unlink($path) && file_exists($path)) {
                    $this->logError('Failed to delete file: ' . $path, $repoName, $userId);
                    throw new RuntimeException("Failed to delete file: {$path}");
                }
            }
        }

        clearstatcache(true, $directory);

        if (count(array_diff(scandir($directory), ['.', '..'])) !== 0) {
            $this->logError('Directory not empty after attempting to delete: ' . $directory, $repoName, $userId);
            throw new RuntimeException("Directory not empty after attempting to delete: {$directory}");
        }

        if (!@rmdir($directory) && is_dir($directory)) {
            $this->logError('Failed to delete directory: ' . $directory, $repoName, $userId);
            throw new RuntimeException("Failed to delete directory: {$directory}");
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
        // Check if it's already an absolute path
        if (is_dir($path)) {
            return realpath($path);
        }

        // Try as relative path from current working directory
        $resolved = realpath($path);
        if ($resolved && is_dir($resolved)) {
            return $resolved;
        }

        // Try as relative path from base path
        $basePath = base_path($path);
        $resolved = realpath($basePath);
        if ($resolved && is_dir($resolved)) {
            return $resolved;
        }

        return null;
    }

    /**
     * Create a symlink from source to destination.
     *
     * @param string $source
     * @param string $destination
     * @return void
     * @throws RuntimeException
     */
    protected function createSymlink(string $source, string $destination, string $repoName, ?int $userId = null): void
    {
        // Ensure the plugins directory exists
        $pluginsDir = dirname($destination);
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }

        // Create symlink
        if (!symlink($source, $destination)) {
            $this->logError('Failed to create symlink from ' . $source . ' to ' . $destination, $repoName, $userId);
            throw new RuntimeException("Failed to create symlink from {$source} to {$destination}");
        }
    }

    /**
     * Prepare git URL with GITHUB_TOKEN if available.
     *
     * @param string $url
     * @return string
     */
    protected function prepareGitUrl(string $url): string
    {
        $token = env('GITHUB_TOKEN');

        if ($token && str_starts_with($url, 'https://github.com/')) {
            // Insert token into HTTPS URL
            $url = str_replace('https://github.com/', "https://{$token}@github.com/", $url);
        }

        return $url;
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
        if ($userId) {
            event(new PluginLog($message, $type, $pluginName, $userId));
        } else {
            if ($type === 'done') {
                \Log::info($message);
            } elseif ($type === 'error') {
                \Log::error($message);
            } else {
                \Log::info($message);
            }
        }
    }
}
