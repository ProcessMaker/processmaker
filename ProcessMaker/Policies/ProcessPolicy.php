<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessPolicy
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
     * Determine whether the user can start the process.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Process  $process
     * @return mixed
     */
    public function start(User $user, Process $process)
    {
        $groupIds = $user->groups->pluck('id');
        
        if ($process->groupsCanStart->whereIn('id', $groupIds)->count()) {
            return true;
        }
        
        if ($process->usersCanStart->where('id', $user->id)->count()) {
            return true;
        }

        return false;
    }
}
