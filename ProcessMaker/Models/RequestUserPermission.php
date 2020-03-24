<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

class RequestUserPermission extends Model
{
    protected $table = "request_user_permissions";

    protected $connection = 'data';

    protected $fillable = [
        'request_id',
        'user_id',
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
