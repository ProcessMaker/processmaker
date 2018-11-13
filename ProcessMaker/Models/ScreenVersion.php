<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class ScreenVersion extends Model
{
    protected $casts = [
        'config' => 'array'
    ];
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];
}
