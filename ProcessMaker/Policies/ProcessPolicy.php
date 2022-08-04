<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;

class ProcessPolicy
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
     * Determine whether the user can start the process.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Process  $process
     * @return mixed
     */
    public function start(User $user, Process $process)
    {
        $userGroupIds = $user->groups->pluck('id')->all();
        $nestedGroupIds = GroupMember::where('member_type', Group::class)->whereIn('member_id', $userGroupIds)->pluck('group_id')->all();
        $groupIds = array_merge($userGroupIds, $nestedGroupIds);

        if ($process->groupsCanStart(request()->query('event'))->whereIn('id', $groupIds)->count()) {
            return true;
        }

        $usersCanStart = $process->usersCanStart(
            request()->query('event')
        )->pluck('id');

        $userCanStartAsProcessManager = array_reduce($process->getStartEvents(),
            function ($carry, $item) use ($process, $user) {
                if (array_key_exists('assignment', $item)) {
                    $carry = $carry || ($item['assignment'] === 'process_manager' && $process->manager_id === $user->id);
                }

                return $carry;
            },
            false);

        if (
            $usersCanStart->contains($user->id) ||
            $usersCanStart->contains(app(AnonymousUser::class)->id) ||
            $userCanStartAsProcessManager
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the process.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Process  $process
     * @return bool
     */
    public function cancel(User $user, Process $process)
    {
        $groupIds = $user->groups->pluck('id');

        if ($process->groupsCanCancel()->whereIn('id', $groupIds)->exists()) {
            return true;
        }

        if ($process->usersCanCancel()->where('id', $user->id)->exists()) {
            return true;
        }

        if (
            $process->manager_id === $user->id &&
            $process->getProperty('manager_can_cancel_request') === true
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can edit data.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Process  $process
     * @return bool
     */
    public function editData(User $user, Process $process)
    {
        $groupIds = $user->groups->pluck('id');

        if ($process->groupsCanEditData()->whereIn('id', $groupIds)->exists()) {
            return true;
        }

        if ($process->usersCanEditData()->where('id', $user->id)->exists()) {
            return true;
        }

        return false;
    }
}
