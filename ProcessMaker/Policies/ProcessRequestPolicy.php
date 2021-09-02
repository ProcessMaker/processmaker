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

        if ($processRequest->hasUserParticipated($user)) {
            return true;
        }

        if ($user->hasPermission('edit-request_data')) {
                return true;
        }

        if ($processRequest->process->usersCanEditData->where('id', $user->id)->count() > 0) {
            return true;
        }

        if ($user->can('view-all_requests')) {
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
        return $user->can('cancel', $processRequest->process)
            || $user->hasPermission('edit-request_data')
            || $user->can('editData', $processRequest->process);
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
     * @return boolean
     */    
    public function editData(User $user, ProcessRequest $request)
    {
        if ($request->status === 'ACTIVE') {
            $permission = 'edit-task_data';
        } else {
            $permission = 'edit-request_data';
        }
        
        if ($user->can($permission)) {
            return true;
        }
        
        return $user->can('editData', $request->process);
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
