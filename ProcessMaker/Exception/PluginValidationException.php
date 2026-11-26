<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginValidationException extends RuntimeException
{
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

    public static function psr4PathDoesNotExist(string $pluginPath, string $path): self
    {
        return new self("Plugin {$pluginPath} has a PSR-4 path that does not exist: {$path}");
    }
}