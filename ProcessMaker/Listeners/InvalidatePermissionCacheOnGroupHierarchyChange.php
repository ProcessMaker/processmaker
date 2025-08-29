<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\GroupMembershipChanged;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Services\PermissionServiceManager;

class InvalidatePermissionCacheOnGroupHierarchyChange
{
    private PermissionServiceManager $permissionService;

    public function __construct(PermissionServiceManager $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle the event.
     */
    public function handle(GroupMembershipChanged $event): void
    {
        try {
            $group = $event->getGroup();
            $action = $event->getAction();

            // All actions (added, removed, updated) require the same cache invalidation logic
            // because they all affect the permission hierarchy for the group and its descendants
            $this->permissionService->invalidateAll();

            Log::info("Successfully invalidated permission cache for group hierarchy change: {$action} for group {$group->id}");
        } catch (\Exception $e) {
            Log::error('Failed to invalidate permission cache on group hierarchy change', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $event->getGroup()->id ?? 'unknown',
                'action' => $event->getAction(),
            ]);
            throw $e;
        }
    }
}
