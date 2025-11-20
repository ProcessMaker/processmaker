<?php

namespace ProcessMaker\Contracts;

interface PermissionStrategyInterface
{
    /**
     * Check if user has permission using this strategy
     */
    public function hasPermission(int $userId, string $permission): bool;

    /**
     * Get strategy name for identification
     */
    public function getStrategyName(): string;

    /**
     * Check if this strategy can handle the permission check
     */
    public function canHandle(string $permission): bool;
}
