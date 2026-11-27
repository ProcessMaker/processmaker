<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginReinstallException extends RuntimeException
{
    public static function notGitRepositoryFromRemote(string $pluginName): self
    {
        return new self(
            "Plugin {$pluginName} is not a git repository and cannot be reinstalled from remote"
        );
    }

    public static function notOnNamedBranch(): self
    {
        return new self(
            'Cannot reinstall: plugin is not on a named branch (detached HEAD or unknown state)'
        );
    }
}