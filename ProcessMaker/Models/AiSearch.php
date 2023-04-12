<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use ProcessMaker\Models\ProcessMakerModel;

class AiSearch extends ProcessMakerModel
{
    use HasFactory;
    use SoftDeletes;

    protected function getResponseAttribute($value)
    {
        return json_decode($value, true);
    }
}
