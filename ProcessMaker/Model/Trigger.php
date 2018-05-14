<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use ProcessMaker\Model\Process;

class Trigger extends Model
{
    use Uuid;

    /**
     * Get the process we belong to.
     * 
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }


}
