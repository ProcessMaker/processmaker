<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\User;

class NotificationPolicy
{
    use HandlesAuthorization;

    private function userCan(User $user, Notification $notification)
    {
        if (
            $notification->notifiable_type == User::class
            && $notification->notifiable_id == $user->id
        ) {
            return true;
        } else {
            return false;
        }
    }

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
     * Determine whether the user can view the notification.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Notification  $notification
     * @return mixed
     */
    public function view(User $user, Notification $notification)
    {
        if ($this->userCan($user, $notification)) {
            return true;
        }

        return Gate::allows('view-notifications', $post);
    }

    /**
     * Determine whether the user can create notifications.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return Gate::allows('create-notifications', $post);
    }

    /**
     * Determine whether the user can update the notification.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Notification  $notification
     * @return mixed
     */
    public function update(User $user, Notification $notification)
    {
        if ($this->userCan($user, $notification)) {
            return true;
        }

        return Gate::allows('create-notifications', $post);
    }

    /**
     * Determine whether the user can delete the notification.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Models\Notification  $notification
     * @return mixed
     */
    public function delete(User $user, Notification $notification)
    {
        if ($this->userCan($user, $notification)) {
            return true;
        }

        return Gate::allows('delete-notifications', $post);
    }
}
