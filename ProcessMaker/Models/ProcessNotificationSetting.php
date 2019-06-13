<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessNotificationSetting extends Model
{
    protected $connection = 'spark';

    public $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    public $guarded = ['process_id'];
}
