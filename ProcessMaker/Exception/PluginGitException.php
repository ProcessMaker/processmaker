<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class PluginGitException extends RuntimeException
{
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

    public static function failedToFetchBranches(string $stderr): self
    {
        return new self('Failed to fetch branches: ' . $stderr);
    }
}