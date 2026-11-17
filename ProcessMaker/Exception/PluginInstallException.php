<?php

namespace ProcessMaker\Exception;

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

    public static function gitCloneTimedOut(string $repoName): self
    {
        return new self("Git clone exceeded the timeout while installing {$repoName}.");
    }

    public static function failedToCloneRepository(string $stderr): self
    {
        return new self('Failed to clone repository: ' . $stderr);
    }

    public static function failedToFetchRepository(string $stderr): self
    {
        return new self('Failed to fetch repository: ' . $stderr);
    }

    public static function cannotDetermineDefaultBranch(): self
    {
        return new self('Could not determine default branch for plugin repository');
    }

    public static function failedToPullRepository(string $stderr): self
    {
        return new self('Failed to pull repository: ' . $stderr);
    }

    public static function failedToCheckoutBranch(string $branch, string $stderr): self
    {
        return new self("Failed to checkout branch {$branch}: {$stderr}");
    }

    public static function failedToCheckoutTag(string $tag, string $stderr): self
    {
        return new self("Failed to checkout tag {$tag}: {$stderr}");
    }

    public static function composerJsonNotFound(string $pluginPath): self
    {
        return new self("composer.json not found in plugin: {$pluginPath}");
    }

    public static function invalidComposerJson(string $composerJsonPath, string $jsonError): self
    {
        return new self("Invalid JSON in composer.json at {$composerJsonPath}: {$jsonError}");
    }

    public static function missingPsr4Autoload(): self
    {
        return new self('Plugin must have PSR-4 autoload configuration in composer.json');
    }

    public static function invalidPsr4Namespace(string $foundNamespaces): self
    {
        return new self(
            "Plugin PSR-4 namespace must start with 'ProcessMaker\\Plugins\\'. Found: {$foundNamespaces}"
        );
    }

    public static function failedToFetchBranches(string $stderr): self
    {
        return new self('Failed to fetch branches: ' . $stderr);
    }

    public static function failedToRunComposerInstall(string $stderr): self
    {
        return new self('Failed to run composer install: ' . $stderr);
    }

    public static function failedToCopyFile(string $srcPath, string $destPath): self
    {
        return new self("Failed to copy file: {$srcPath} to {$destPath}");
    }

    public static function psr4PathDoesNotExist(string $pluginPath, string $path): self
    {
        return new self("Plugin {$pluginPath} has a PSR-4 path that does not exist: {$path}");
    }
}