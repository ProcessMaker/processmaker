<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Models\User;
use Illuminate\Auth\Events\Login;

class LoginListener
{
    /**
     * Updated the user "loggedin_at" attribute
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     *
     * @return void
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        $user->timestamps = false;

        $user->setAttribute('loggedin_at', now());
        $user->save();
    }
}
