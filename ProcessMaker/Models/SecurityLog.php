<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    const UPDATED_AT = null;
    
    protected $connection = 'data';
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
    ];
    
    /**
     * Get the associated user, if any.
     *
     */
    public function user()
    {
        return $this->belongsTo('ProcessMaker\Models\User');
    }
}
