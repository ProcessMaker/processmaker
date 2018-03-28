<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Model\Permission;
use ProcessMaker\Model\User;

class ProcessCategoryPolicy
{

    /**
     * Determine if the given process category can be read by the user.
     *
     * @param User $user
     * @return bool
     */
    public function read(User $user)
    {
        return $user->can('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
    }

    /**
     * Determine if the given process category can be written by the user.
     *
     * @param User $user
     * @return bool
     */
    public function write(User $user)
    {
        return $user->can('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
    }
    /**
     * Determine if the given process category can be deleted by the user.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->can('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
    }
}
