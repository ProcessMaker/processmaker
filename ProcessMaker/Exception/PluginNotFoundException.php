<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginNotFoundException extends RuntimeException
{
    public static function forPlugin(string $pluginName): self
    {
        return new self("Plugin not found: {$pluginName}");
    }

    public static function directoryDoesNotExist(string $path): self
    {
        return new self("Plugin directory does not exist: {$path}");
    }
}