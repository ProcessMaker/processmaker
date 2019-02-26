<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

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
        
        if ($process->groupsCanStart(request()->query('event'))->whereIn('id', $groupIds)->count()) {
            return true;
        }
        
        if ($process->usersCanStart(request()->query('event'))->where('id', $user->id)->count()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the process.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Process  $process
     * @return mixed
     */
    public function cancel(User $user, Process $process)
    {
        $groupIds = $user->groups->pluck('id');
        
        if ($process->groupsCanCancel->whereIn('id', $groupIds)->count()) {
            return true;
        }
        
        if ($process->usersCanCancel->where('id', $user->id)->count()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can edit data.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Process  $process
     * @return mixed
     */
    public function editData(User $user, Process $process)
    {
        $groupIds = $user->groups->pluck('id');
        
        if ($process->groupsCanEditData->whereIn('id', $groupIds)->count()) {
            return true;
        }

        if ($process->usersCanEditData->where('id', $user->id)->count()) {
            return true;
        }

        return false;
    }
}
