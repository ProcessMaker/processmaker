<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Model\Permission;
use ProcessMaker\Model\User;

class ProcessPolicy
{

    /**
     * Determine if the given project can be read by the user.
     *
     * @param User $user
     * @return bool
     */
    public function readProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Determine if the given project can be read by the user.
     *
     * @param User $user
     * @return bool
     */
    public function writeProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Determine if the given project can be read by the user.
     *
     * @param User $user
     * @return bool
     */
    public function deleteProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }
}
