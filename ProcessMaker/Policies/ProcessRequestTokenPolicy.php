<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class ProcessRequestTokenPolicy
{
    use HandlesAuthorization;

    /**
     * Run before all methods to determine if the
     * user is an admin and can do everything.
     *
     * @param  User  $user
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
     * @param  User  $user
     * @param  ProcessRequestToken  $processRequestToken
     * @return mixed
     */
    public function view(User $user, ProcessRequestToken $processRequestToken)
    {
        if ($processRequestToken->user_id == $user->id ||
            $processRequestToken->process?->manager_id === $user->id
        ) {
            return true;
        }
        if ($user->canSelfServe($processRequestToken)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the process request token.
     *
     * @param  User  $user
     * @param  ProcessRequestToken  $processRequestToken
     * @return mixed
     */
    public function update(User $user, ProcessRequestToken $processRequestToken)
    {
        if (
            $processRequestToken->user_id === $user->id ||
            $processRequestToken->user_id === app(AnonymousUser::class)->id ||
            $processRequestToken->process?->manager_id === $user->id
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
     * @param  User  $user
     * @param  ProcessRequestToken  $processRequestToken
     * @param  Screen  $screen
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

    /**
     * Determine if a user can rollback the process request.
     *
     * @param  User  $user
     * @param  ProcessRequestToken  $processRequestToken
     *
     * @return bool
     */
    public function rollback(User $user, ProcessRequestToken $task)
    {
        // For now, only the process manager can rollback the request
        return $user->id === $task->process->managerId;
    }

    public function reassign(User $user, ProcessRequestToken $task)
    {
        if ($user->can('update', $task)) {
            $definitions = $task->getDefinition();
            if (empty($definitions['allowReassignment']) || $definitions['allowReassignment'] === 'false') {
                return Response::deny('Not authorized to reassign this task');
            }

            return true;
        } else {
            return Response::deny('Not authorized to update this task');
        }
    }
}
