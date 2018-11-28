<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;


class ScriptVersion extends Model
{
    /**
     * Do not automatically set created_at
     */
    const CREATED_AT = null;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'id',
        'updated_at',
    ];
}
