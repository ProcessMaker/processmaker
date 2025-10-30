<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\PermissionUpdated;
use ProcessMaker\Services\PermissionServiceManager;

class InvalidatePermissionCacheOnUpdate
{
    private PermissionServiceManager $permissionService;

    public function __construct(PermissionServiceManager $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle the event.
     */
    public function handle(PermissionUpdated $event): void
    {
        try {
            // Invalidate cache for user if user permissions were updated
            if ($event->getUserId()) {
                $this->permissionService->invalidateUserCache((int) $event->getUserId());
            }

            // Invalidate cache for group if group permissions were updated
            if ($event->getGroupId()) {
                $this->permissionService->invalidateAll();
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate permission cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => $event->getUserId(),
                'groupId' => $event->getGroupId(),
            ]);
            throw $e; // Re-throw to ensure error is properly handled
        }
    }
}
