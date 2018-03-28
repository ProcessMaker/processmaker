<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Model\Permission;
use ProcessMaker\Model\User;

class ProcessVariablePolicy
{

    /**
     * Determine if the user can read process variables
     *
     * @param User $user
     * @return bool
     */
    public function read(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Determine if the user can write a process variable
     *
     * @param User $user
     * @return bool
     */
    public function write(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Determine if the user can delete a process variable
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }
}
