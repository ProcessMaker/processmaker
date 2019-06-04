<?php

namespace ProcessMaker\Observers;

use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

class UserObserver
{
    /**
     * Handle the user "deleting" event.
     *
     * @param User $user
     * @throws ReferentialIntegrityException
     */
    public function deleting(User $user)
    {
        //Validate if the user has Request processes assigned
        //An user can not be deleted if it has requests
        $query = ProcessRequest::where('user_id', $user->id);
        $count = $query->count();
        if ($count > 0) {
            throw new ReferentialIntegrityException($user, $query->first());
        }
        //Remove comments
        Comment::query()
            ->where('user_id', $user->id)
            ->delete();
    }
}
