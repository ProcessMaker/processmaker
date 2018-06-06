<?php

namespace ProcessMaker\Policies;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\User;

/**
 * Our Application policies attached to our Application eloquent model
 * @package ProcessMaker\Policies
 */
class ApplicationPolicy
{
    /**
     * Determine if the given application can be read by the user.
     * We determine first by permission then by supervisor
     * @param  \ProcessMaker\Model\User  $user
     * @param  \ProcessMaker\Model\Application  $application
     * @return bool
     */
    public function read(User $user, Application $application)
    {
        // First we check if we are in our participation list
        if ($application->hasUserParticipated($user)) {
            return true;
        }

        // Check if we're a process supervisor for this application
        if ($application->process->isSupervisor($user)) {
            return true;
        }


        // Check to see if we have SUMMARY_FORM object permission
        /**
         * @todo perhaps replace with a can() policy check on process policy
         */
        // Let's define the ids we'll look for when finding permissions
        $ids = array_merge(['0', '', $user->USR_UID], $user->groups()->pluck('groups.uid')->toArray());
        // Since this is our last check, we'll just return the result boolean
        return DB::table('object_permissions')->where('process_id', $application->process->id)
            ->where('action', 'VIEW')
            ->whereIn('obj_type', ['ANY', 'SUMMARY_FORM'])
            ->whereIn('case_status', ['ALL', '', '0', $application->APP_STATUS])
            ->whereIn('user_id', $ids)
            ->exists();
    }
}
