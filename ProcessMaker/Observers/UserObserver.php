<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\User;

class UserObserver
{
    /**
     * Handle the user "deleted" event.
     *
     * @param  User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        Comment::query()
            ->where('user_id', $user->id)
            ->delete();
    }
}
