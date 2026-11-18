<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class OctaneRuntimeSyncException extends RuntimeException
{
    public static function supervisorRereadFailed(string $stderr): self
    {
        return new self('supervisorctl reread failed: ' . $stderr);
    }

    public static function supervisorUpdateFailed(string $stderr): self
    {
        return new self('supervisorctl update failed: ' . $stderr);
    }

    public static function tenantDomainEmpty(int $tenantId): self
    {
        return new self("Tenant {$tenantId} has an empty domain; cannot build edge routes.");
    }
}