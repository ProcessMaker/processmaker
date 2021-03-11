<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\AnonymousUser;
use Illuminate\Support\Facades\Request;

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
        if (
            $processRequestToken->user_id === $user->id || 
            $processRequestToken->user_id === app(AnonymousUser::class)->id
        ) {
            return true;
        }
        if ($user->canSelfServe($processRequestToken)) {
            return true;
        }
    }

    /**
     * Determine if the user can view a screen associated with the task
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequestToken  $processRequestToken
     * @param  \ProcessMaker\Models\Screen  $screen
     * @return mixed
     */
    public function viewScreen(User $user, ProcessRequestToken $task, Screen $screen)
    {
        if (!$user->can('update', $task)) {
            return false;
        }

        $screenIds = $task->getScreenAndNestedIds();
        if (!in_array($screen->id, $screenIds)) {
            return false;
        }

        return true;
    }
    
}
