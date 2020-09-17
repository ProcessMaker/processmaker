<?php

namespace ProcessMaker\Models;

use Illuminate\Contracts\Session\Session;
use ProcessMaker\Models\ProcessRequestToken;

class AnonymousUser extends User
{
    const ANONYMOUS_USERNAME = '_pm4_anon_user';

    protected $table = 'users';

    public function canEdit(Session $session, ProcessRequestToken $task)
    {
    }
}