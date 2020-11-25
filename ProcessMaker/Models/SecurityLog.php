<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\ExtendedPMQL;

class SecurityLog extends Model
{
    use ExtendedPMQL;
    
    const CREATED_AT = 'occurred_at';
    const UPDATED_AT = null;
    
    protected $connection = 'data';
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'occurred_at',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'object',
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
