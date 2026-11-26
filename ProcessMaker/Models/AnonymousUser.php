<?php

namespace ProcessMaker\Models;

use Illuminate\Contracts\Session\Session;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

class AnonymousUser extends User
{
    const ANONYMOUS_USERNAME = '_pm4_anon_user';

    protected $table = 'users';

    public $isAnonymous = true;

    /**
     * Determine if the given user is the anonymous system user.
     *
     * Covers both the AnonymousUser instance resolved by the `anon` guard and a
     * plain User model loaded for the same record (e.g. by Passport's TokenGuard),
     * which does not carry the $isAnonymous flag.
     */
    public static function isAnonymousIdentity($user): bool
    {
        if ($user instanceof self) {
            return true;
        }

        return $user instanceof User && $user->username === self::ANONYMOUS_USERNAME;
    }

    public function receivesBroadcastNotificationsOn($notification)
    {
        $class = str_replace('\\', '.', get_parent_class());

        return $class . '.' . $this->getKey();
    }
}
