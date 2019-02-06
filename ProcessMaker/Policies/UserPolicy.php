<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
     * Determine whether the user can view the user.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\User  $targetUser
     * @return mixed
     */
    public function view(User $user, User $targetUser)
    {
        if ($targetUser->id == $user->id) {
            return true;
        }
        return $user->can('view', $targetUser);
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\User  $targetUser
     * @return mixed
     */
    public function edit(User $user, User $targetUser)
    {
        if ($targetUser->id == $user->id) {
            return true;
        }
        return $user->hasPermission('edit-users');
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\User  $targetUser
     * @return mixed
     */
    public function destroy(User $user, User $targetUser)
    {
        return $user->can('delete', $targetUser);
    }
}
