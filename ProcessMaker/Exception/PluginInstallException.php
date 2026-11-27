<?php

namespace ProcessMaker\Exception;

use ProcessMaker\Managers\PluginManager;
use RuntimeException;

class PluginInstallException extends RuntimeException
{
    public static function branchAndTagMutuallyExclusive(): self
    {
        return new self('Cannot specify both --branch and --tag');
    }

    public static function failedToRemoveExistingSymlink(string $pluginPath): self
    {
        return new self("Failed to remove existing plugin symlink: {$pluginPath}");
    }

    public static function zipFileNotFound(string $zipPath): self
    {
        return new self("Zip file not found: {$zipPath}");
    }

    public static function failedToOpenZipFile(string $zipPath, int $errorCode): self
    {
        return new self("Failed to open zip file: {$zipPath} (error code: {$errorCode})");
    }

    public static function failedToCreateTempDirectory(string $directory): self
    {
        return new self("Failed to create temp directory: {$directory}");
    }

    public static function cannotExtractRepositoryNameFromUrl(string $repoUrl): self
    {
        return new self("Could not extract repository name from URL: {$repoUrl}");
    }

    public static function failedToRunComposerInstall(string $stderr): self
    {
        return new self('Failed to run composer install: ' . $stderr);
    }

    public static function failedToCopyFile(string $srcPath, string $destPath): self
    {
        return new self("Failed to copy file: {$srcPath} to {$destPath}");
    }

    public static function emptyRepository(): self
    {
        return new self(PluginManager::EMPTY_REPOSITORY_MESSAGE);
    }
}
