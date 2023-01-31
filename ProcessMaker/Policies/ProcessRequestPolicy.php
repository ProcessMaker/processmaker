<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

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
        // Policy defined in ForUserScope
        return ProcessRequest::forUser($user)
            ->where('process_requests.id', $processRequest->id)
            ->exists();
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

        return $user->can('cancel', $processRequest->processVersion)
            || $user->hasPermission('edit-request_data')
            || $user->can('editData', $processRequest->processVersion);
    }

    /**
     * Determine whether the user can update the process request.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequest  $processRequest
     * @return mixed
     */
    public function destroy(User $user, ProcessRequest $processRequest)
    {
        if ($processRequest->user_id == $user->id) {
            return true;
        }
    }

    /**
     * Determine whether the user can edit request data.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Process  $process
     * @return bool
     */
    public function editData(User $user, ProcessRequest $request)
    {
        $permission = $request->status === 'ACTIVE'
            ? 'edit-task_data'
            : 'edit-request_data';

        if ($user->can($permission)) {
            return true;
        }

        return $user->can('editData', $request->processVersion);
    }

    /**
     * User has access if participates in the request.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\ProcessRequest  $processRequest
     *
     * @return mixed
     */
    public function participate(User $user, ProcessRequest $processRequest)
    {
        if ($processRequest->user_id == $user->id) {
            return true;
        }

        return $processRequest->hasUserParticipated($user);
    }
}
