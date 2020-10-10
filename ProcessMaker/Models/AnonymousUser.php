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

    public function receivesBroadcastNotificationsOn($notification) {
        $class = str_replace('\\', '.', get_parent_class());
        return $class.'.'.$this->getKey();
    }
}