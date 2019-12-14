<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessRequestTokenPolicy
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
     * Determine whether the user can view the process request token.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequestToken  $processRequestToken
     * @return mixed
     */
    public function view(User $user, ProcessRequestToken $processRequestToken)
    {
        if ($processRequestToken->user_id == $user->id) {
            return true;
        }
        if ($user->canSelfServe($processRequestToken)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the process request token.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequestToken  $processRequestToken
     * @return mixed
     */
    public function update(User $user, ProcessRequestToken $processRequestToken)
    {
        if ($processRequestToken->user_id == $user->id) {
            return true;
        }
        if ($user->canSelfServe($processRequestToken)) {
            return true;
        }
    }    
    
}
