<?php

namespace ProcessMaker\Listeners;

use Illuminate\Auth\Events\Login;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\InboxRulesNotification;

class LoginListener
{
    /**
     * Updated the user "loggedin_at" attribute
     *
     * @param  Login  $event
     *
     * @return void
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        if ($user->loggedin_at && InboxRuleLog::hasChangesSince($user->id, $user->loggedin_at)) {
            $user->notify(new InboxRulesNotification($user->loggedin_at));
        }

        $user->timestamps = false;

        $user->setAttribute('loggedin_at', now());
        $user->save();
    }
}
