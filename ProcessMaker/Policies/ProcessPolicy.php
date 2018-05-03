<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Model\Permission;
use ProcessMaker\Model\User;

/**
 * Authorization rules for processes.
 *
 */
class ProcessPolicy
{

    /**
     * Verify if the user can read the process.
     *
     * @param User $user
     * @return bool
     */
    public function read(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Verify if the user can update the process.
     *
     * @param User $user
     * @return bool
     */
    public function write(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Verify if the user can delete the process.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Verify if the user can read the process files.
     *
     * @param User $user
     * @return bool
     */
    public function readProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Verify if the user can update the process files.
     *
     * @param User $user
     * @return bool
     */
    public function writeProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }

    /**
     * Verify if the user can delete the process files.
     *
     * @param User $user
     * @return bool
     */
    public function deleteProcessFiles(User $user)
    {
        return $user->can('has-permission', Permission::PM_FACTORY);
    }
}
