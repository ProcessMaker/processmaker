<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

class UserObserver
{
    /**
     * Handle the user "deleting" event.
     *
     * @throws ReferentialIntegrityException
     */
    public function deleting(User $user)
    {
        // Validate if the user has Request processes assigned
        // An user can not be deleted if it has requests
        $query = ProcessRequest::where('user_id', $user->id);
        $count = $query->count();
        if ($count > 0) {
            throw new ReferentialIntegrityException($user, $query->first());
        }
        // Remove comments
        Comment::query()
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Handle the user "created" event.
     */
    public function created(User $user): void
    {
        $perList = [
            'view-process-catalog',
            'view-my_requests',
        ];
        $permissionIds = Permission::whereIn('name', $perList)->pluck('id')->toArray();
        $user->permissions()->attach($permissionIds);
    }
}
