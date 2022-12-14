<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

class RequestUserPermission extends ProcessMakerModel
{
    protected $table = 'request_user_permissions';

    protected $fillable = [
        'request_id',
        'user_id',
        'can_view',
    ];

    public function request()
    {
        return $this->belongsTo(ProcessRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
