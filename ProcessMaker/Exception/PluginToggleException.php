<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginToggleException extends RuntimeException
{
    public static function failedToDisable(string $pluginName): self
    {
        return new self("Failed to disable plugin: {$pluginName}");
    }

    public static function failedToEnable(string $pluginName): self
    {
        return new self("Failed to enable plugin: {$pluginName}");
    }
}