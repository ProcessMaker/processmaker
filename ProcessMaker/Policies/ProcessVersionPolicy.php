<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\User;

class ProcessVersionPolicy
{
    use HandlesAuthorization;

    /**
     * Run before all methods to determine if the
     * user is an admin and can do everything.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @return mixed
     */
    public function before(User $user)
    {
        if ($user->is_administrator) {
            return true;
        }
    }

    /**
     * Determine whether the user can cancel the process version.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessVersion  $processVersion
     *
     * @return bool
     */
    public function cancel(User $user, ProcessVersion $processVersion)
    {
        $groupIds = $user->groups->pluck('id');

        if ($processVersion->groupsCanCancel()->whereIn('id', $groupIds)->exists()) {
            return true;
        }

        if ($processVersion->usersCanCancel()->where('id', $user->id)->exists()) {
            return true;
        }

        if (
            $processVersion->manager_id === $user->id &&
            $processVersion->getProperty('manager_can_cancel_request') === true
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can edit data
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessVersion  $processVersion
     *
     * @return bool
     */
    public function editData(User $user, ProcessVersion $processVersion)
    {
        $groupIds = $user->groups->pluck('id');

        if ($processVersion->groupsCanEditData()->whereIn('id', $groupIds)->exists()) {
            return true;
        }

        if ($processVersion->usersCanEditData()->where('id', $user->id)->exists()) {
            return true;
        }

        return false;
    }
}
