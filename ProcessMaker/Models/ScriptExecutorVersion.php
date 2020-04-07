<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class ScriptExecutorVersion extends Model
{
    protected $fillable = [
        'title', 'description', 'language', 'config'
    ];
}
