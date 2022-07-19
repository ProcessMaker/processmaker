<?php

namespace ProcessMaker\Models;

use ProcessMaker\Traits\Exportable;
use Illuminate\Database\Eloquent\Model;

class Processable extends Model
{
    use Exportable;
    
    public $timestamps = false;
}
