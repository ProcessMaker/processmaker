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
     * @param  \ProcessMaker\ProcessVersion  $process
     * @return mixed
     */
    public function cancel(User $user, ProcessVersion $processVersion)
    {
        $groupIds = $user->groups->pluck('id');
        
        // TODO: get from current version
        if ($processVersion->process->groupsCanCancel->whereIn('id', $groupIds)->count()) {
            return true;
        }
        
        // TODO: get from current version
        if ($processVersion->process->usersCanCancel->where('id', $user->id)->count()) {
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

}
