<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessRequestPolicy
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
     * Determine whether the user can view the process request.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequest  $processRequest
     * @return mixed
     */
    public function view(User $user, ProcessRequest $processRequest)
    {
        if ($processRequest->user_id == $user->id) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the process request.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequest  $processRequest
     * @return mixed
     */
    public function update(User $user, ProcessRequest $processRequest)
    {
        if ($processRequest->user_id == $user->id) {
            return true;
        }
    }    
}
