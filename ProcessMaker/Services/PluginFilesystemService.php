<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use ProcessMaker\Exception\PluginFilesystemException;
use ProcessMaker\Exception\PluginInstallException;

class PluginFilesystemService
{
    public function resolveLocalPath(string $path): ?string
    {
        foreach ([$path, base_path($path)] as $candidate) {
            if (is_dir($candidate)) {
                $resolved = realpath($candidate);

                return $resolved !== false ? $resolved : null;
            }

            $resolved = realpath($candidate);
            if ($resolved !== false && is_dir($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    public function copyDirectory(string $source, string $destination, string $pluginName, ?int $userId, PluginLogService $log): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        foreach (array_diff(scandir($source), ['.', '..']) as $file) {
            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath, $pluginName, $userId, $log);
            } elseif (!copy($sourcePath, $destinationPath)) {
                $log->error("Failed to copy file: {$sourcePath} to {$destinationPath}", $pluginName, $userId);
                throw PluginInstallException::failedToCopyFile($sourcePath, $destinationPath);
            }
        }
    }

    public function removePluginPath(string $path, string $pluginName, ?int $userId, PluginLogService $log): void
    {
        if (is_link($path)) {
            $log->running('Removing plugin symlink (source directory is kept)...', $pluginName, $userId);
            if (!@unlink($path)) {
                $log->error('Failed to remove plugin symlink: ' . $path, $pluginName, $userId);
                throw PluginFilesystemException::failedToRemovePluginSymlink($path);
            }

            return;
        }

        $this->deleteDirectory($path, $pluginName, $userId, $log);
    }

    public function deleteDirectory(string $directory, string $pluginName, ?int $userId, PluginLogService $log): void
    {
        if (is_link($directory)) {
            if (!@unlink($directory)) {
                $log->error('Failed to remove symlink: ' . $directory, $pluginName, $userId);
                throw PluginFilesystemException::failedToRemoveSymlink($directory);
            }

            return;
        }
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory), ['.', '..']) as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path) && !is_link($path)) {
                $this->deleteDirectory($path, $pluginName, $userId, $log);
            } elseif (!@unlink($path) && file_exists($path)) {
                $log->error('Failed to delete file: ' . $path, $pluginName, $userId);
                throw PluginFilesystemException::failedToDeleteFile($path);
            }
        }

        clearstatcache(true, $directory);
        if (count(array_diff(scandir($directory), ['.', '..'])) !== 0) {
            $log->error('Directory not empty after attempting to delete: ' . $directory, $pluginName, $userId);
            throw PluginFilesystemException::directoryNotEmptyAfterDelete($directory);
        }
        if (!@rmdir($directory) && is_dir($directory)) {
            $log->error('Failed to delete directory: ' . $directory, $pluginName, $userId);
            throw PluginFilesystemException::failedToDeleteDirectory($directory);
        }
    }
}
