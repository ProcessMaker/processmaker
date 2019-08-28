<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Script;

class ScriptCategory extends Model
{
    protected $connection = 'processmaker';

    public function scripts()
    {
        return $this->hasMany(Script::class);
    }
}