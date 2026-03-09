<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Arr;
use function Illuminate\Support\artisan_binary;
use Illuminate\Support\Facades\Artisan;
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
     * @return void
     * @throws RuntimeException
     */
    public function install(string $repoUrl, ?string $branch = null, ?string $tag = null): void
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

            $this->logRunning("Starting installation of plugin from local path: {$repoName}", $repoName);

            // Create symlink if target doesn't exist
            if (is_link($pluginPath) || is_dir($pluginPath)) {
                // throw new RuntimeException("Plugin directory or symlink already exists: {$pluginPath}");
                $this->logRunning("Plugin directory or symlink already exists: {$pluginPath}", $repoName);
            } else {
                $this->logRunning('Creating symlink to local path...', $repoName);
                $this->createSymlink($localPath, $pluginPath);
            }
        } else {
            // Extract repository name from URL
            $repoName = $this->extractRepoName($repoUrl);
            $pluginPath = storage_path('plugins') . '/' . $repoName;

            $this->logRunning("Starting installation of plugin: {$repoName}", $repoName);

            // Clone or pull the repository
            if (is_dir($pluginPath)) {
                $this->logRunning('Plugin directory exists, pulling latest changes...', $repoName);
                $this->pullRepository($pluginPath);
            } else {
                $this->logRunning('Cloning repository...', $repoName);
                $this->cloneRepository($repoUrl, $pluginPath);
            }

            // Checkout branch or tag if specified
            if ($branch) {
                $this->logRunning("Checking out branch: {$branch}", $repoName);
                $this->checkoutBranch($pluginPath, $branch);
            } elseif ($tag) {
                $this->logRunning("Checking out tag: {$tag}", $repoName);
                $this->checkoutTag($pluginPath, $tag);
            }
        }

        // Validate composer.json
        $this->logRunning('Validating plugin...', $repoName);
        $this->validatePlugin($pluginPath);

        // Run composer install
        $this->logRunning('Installing dependencies...', $repoName);
        $this->runComposerInstall($pluginPath);

        // Run plugin install command if it exists
        $installCommand = "{$repoName}:install";

        $this->logRunning('Running plugin install command...', $repoName);
        $this->runCommand($installCommand, $repoName);

        // Rebuild route cache so we pick up the new routes from the plugin
        $this->logRunning('Rebuilding route cache...', $repoName);
        $this->runCommand('route:cache', $repoName);

        $this->logDone('Plugin installed successfully', $repoName);
    }

    /**
     * Uninstall a plugin.
     *
     * @param string $pluginName
     * @return void
     * @throws RuntimeException
     */
    public function uninstall(string $pluginName): void
    {
        $pluginPath = storage_path('plugins') . '/' . $pluginName;

        if (!is_dir($pluginPath)) {
            throw new RuntimeException("Plugin not found: {$pluginName}");
        }

        $this->logRunning("Starting uninstallation of plugin: {$pluginName}", $pluginName);

        // Run plugin uninstall command if it exists
        $uninstallCommand = "{$pluginName}:uninstall";
        $this->logRunning('Running plugin uninstall command...', $pluginName);
        $this->runCommand($uninstallCommand, $pluginName);

        // Delete the plugin directory
        $this->logRunning('Removing plugin directory...', $pluginName);
        $this->deleteDirectory($pluginPath);

        // Rebuild route cache so we remove the routes from the plugin
        $this->logRunning('Rebuilding route cache...', $pluginName);
        $this->runCommand('route:cache', $pluginName);

        $this->logDone('Plugin uninstalled successfully', $pluginName);
    }

    private function runCommand(string $command, string $pluginName): void
    {
        $result = Process::run(array_filter([
            php_binary(),
            artisan_binary(),
            $command,
        ]));

        if (!$result->successful()) {
            if (str_contains($result->output(), 'is not defined')) {
                $this->logRunning("Plugin does not have a {$command} command", $pluginName);
            } else {
                $this->logError("Plugin {$command} command failed. Got output:\n\n{$result->output()}", $pluginName);
            }
        } else {
            $this->logRunning("Plugin {$command} command output:\n\n{$result->output()}", $pluginName);
        }
    }

    /**
     * Install a plugin from a zip file path.
     *
     * @param string $zipPath Full path to the uploaded zip file
     * @return void
     * @throws RuntimeException
     */
    public function installFromZip(string $zipPath): void
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
            $this->validatePlugin($pluginSourceDir);

            $repoName = basename($pluginSourceDir);
            $pluginPath = storage_path('plugins') . '/' . $repoName;

            $this->logRunning("Installing plugin from zip: {$repoName}", $repoName);

            // Ensure plugins directory exists
            if (!is_dir(storage_path('plugins'))) {
                mkdir(storage_path('plugins'), 0755, true);
            }

            // Remove existing plugin directory if present
            if (is_dir($pluginPath)) {
                $this->deleteDirectory($pluginPath);
            }

            // Copy extracted directory to plugins folder
            $this->copyDirectory($pluginSourceDir, $pluginPath);

            // Run composer install
            $this->logRunning('Installing dependencies...', $repoName);
            $this->runComposerInstall($pluginPath);

            // Run plugin install command if it exists
            $installCommand = "{$repoName}:install";
            $this->logRunning('Running plugin install command...', $repoName);
            $this->runCommand($installCommand, $repoName);

            // Rebuild route cache
            $this->logRunning('Rebuilding route cache...', $repoName);
            $this->runCommand('route:cache', $repoName);

            $this->logDone('Plugin installed successfully', $repoName);
        } finally {
            // Always clean up temp directory and zip file
            if (is_dir($tmpExtractDir)) {
                $this->deleteDirectory($tmpExtractDir);
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
    public function toggle(string $pluginName): bool
    {
        $pluginsFolder = storage_path('plugins');

        // Check if currently enabled (no underscore prefix)
        $enabledPath = $pluginsFolder . '/' . $pluginName;
        $disabledName = '_' . ltrim($pluginName, '_');
        $disabledPath = $pluginsFolder . '/' . $disabledName;

        if (is_dir($enabledPath)) {
            // Disable it
            if (!rename($enabledPath, $disabledPath)) {
                throw new RuntimeException("Failed to disable plugin: {$pluginName}");
            }

            return false;
        }

        if (is_dir($disabledPath)) {
            // Enable it
            $enabledName = ltrim($pluginName, '_');
            $enabledPath = $pluginsFolder . '/' . $enabledName;
            if (!rename($disabledPath, $enabledPath)) {
                throw new RuntimeException("Failed to enable plugin: {$pluginName}");
            }

            return true;
        }

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
                    'folder' => $dirName,
                    'description' => $plugin->getDescription() ?? 'No description',
                    'version' => $composerJson['version'] ?? null,
                    'branch' => $reference,
                    'enabled' => !$isDisabled,
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
        // Remove .git suffix if present
        $repoUrl = rtrim($repoUrl, '.git');
        $repoUrl = rtrim($repoUrl, '/');

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
    protected function cloneRepository(string $repoUrl, string $destination): void
    {
        // Handle GITHUB_TOKEN if present
        $url = $this->prepareGitUrl($repoUrl);

        $process = Process::run("git clone {$url} {$destination}");

        if (!$process->successful()) {
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
    protected function pullRepository(string $pluginPath): void
    {
        $process = Process::path($pluginPath)->run('git pull');

        if (!$process->successful()) {
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
    protected function checkoutBranch(string $pluginPath, string $branch): void
    {
        $process = Process::path($pluginPath)->run("git checkout {$branch}");

        if (!$process->successful()) {
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
    protected function checkoutTag(string $pluginPath, string $tag): void
    {
        $process = Process::path($pluginPath)->run("git checkout tags/{$tag}");

        if (!$process->successful()) {
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
    protected function validatePlugin(string $pluginPath): void
    {
        $composerJsonPath = $pluginPath . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException("composer.json not found in plugin: {$pluginPath}");
        }

        $content = file_get_contents($composerJsonPath);
        $composerJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "Invalid JSON in composer.json at {$composerJsonPath}: " . json_last_error_msg()
            );
        }

        // Check PSR-4 autoload namespace
        $psr4Namespaces = Arr::get($composerJson, 'autoload.psr-4', []);

        if (empty($psr4Namespaces)) {
            throw new RuntimeException('Plugin must have PSR-4 autoload configuration in composer.json');
        }

        $valid = false;
        foreach ($psr4Namespaces as $namespace => $path) {
            if (str_starts_with($namespace, 'ProcessMaker\\Plugins\\')) {
                $valid = true;
                break;
            }
            if (str_starts_with($namespace, 'ProcessMaker\\Package\\')) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new RuntimeException(
                "Plugin PSR-4 namespace must start with 'ProcessMaker\\Plugins\\'. Found: " .
                implode(', ', array_keys($psr4Namespaces))
            );
        }
    }

    /**
     * Run composer install in the plugin directory.
     *
     * @param string $pluginPath
     * @return void
     * @throws RuntimeException
     */
    protected function runComposerInstall(string $pluginPath): void
    {
        $process = Process::path($pluginPath)->run('composer install --no-interaction');

        if (!$process->successful()) {
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
    protected function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = array_diff(scandir($source), ['.', '..']);

        foreach ($files as $file) {
            $srcPath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath);
            } else {
                if (!copy($srcPath, $destPath)) {
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
    protected function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        if (!rmdir($directory)) {
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
    protected function createSymlink(string $source, string $destination): void
    {
        // Ensure the plugins directory exists
        $pluginsDir = dirname($destination);
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }

        // Create symlink
        if (!symlink($source, $destination)) {
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
    protected function logRunning(string $message, string $pluginName): void
    {
        $this->log($message, 'running', $pluginName);
    }

    /**
     * Log an error status message.
     *
     * @param string $message
     * @param string $pluginName
     * @return void
     */
    protected function logError(string $message, string $pluginName): void
    {
        $this->log($message, 'error', $pluginName);
    }

    /**
     * Log a done status message.
     *
     * @param string $message
     * @param string $pluginName
     * @return void
     */
    protected function logDone(string $message, string $pluginName): void
    {
        $this->log($message, 'done', $pluginName);
    }

    /**
     * Log a plugin event.
     *
     * @param string $message
     * @param string $type
     * @param string $pluginName
     * @return void
     */
    protected function log(string $message, string $type, string $pluginName): void
    {
        event(new PluginLog($message, $type, $pluginName));
    }
}
