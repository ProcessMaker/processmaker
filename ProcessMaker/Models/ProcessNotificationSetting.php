<?php

namespace ProcessMaker\Models;

class ProcessNotificationSetting extends ProcessMakerModel
{
    public $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    public $guarded = ['process_id'];
}
