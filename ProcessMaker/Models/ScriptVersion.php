<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class ScriptVersion extends Model
{
    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];
}
