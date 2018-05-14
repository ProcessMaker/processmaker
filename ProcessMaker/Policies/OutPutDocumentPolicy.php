<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Model\Permission;
use ProcessMaker\Model\User;

class OutPutDocumentPolicy
{
    /**
     * Determine if the trigger can be read by the user.
     *
     * @param User $user
     * @return bool
     */
    public function read(User $user): bool
    {
        return $user->can('has-permission', [Permission::PM_FACTORY, Permission::PM_CASES]);
    }

    /**
     * Determine if the trigger can be written by the user.
     *
     * @param User $user
     * @return bool
     */
    public function write(User $user): bool
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Determine if the trigger can be deleted by the user.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

}
