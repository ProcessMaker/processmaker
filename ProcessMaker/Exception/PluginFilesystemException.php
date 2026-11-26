<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginFilesystemException extends RuntimeException
{
    public static function failedToRemovePluginSymlink(string $pluginPath): self
    {
        return new self("Failed to remove plugin symlink: {$pluginPath}");
    }

    public static function failedToRemoveSymlink(string $path): self
    {
        return new self("Failed to remove symlink: {$path}");
    }

    public static function failedToDeleteFile(string $path): self
    {
        return new self("Failed to delete file: {$path}");
    }

    public static function directoryNotEmptyAfterDelete(string $directory): self
    {
        return new self("Directory not empty after attempting to delete: {$directory}");
    }

    public static function failedToDeleteDirectory(string $directory): self
    {
        return new self("Failed to delete directory: {$directory}");
    }
}