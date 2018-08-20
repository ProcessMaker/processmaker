<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;

/**
 * Represents a configuration parameter in the system for persistence.
 * See: \ProcessMaker\Config\Repository
 */
class Configuration extends Model
{
    use Uuid; 

    protected $fillable = [
        'parameter',
        'value'
    ];

    public $timestamps = false;
}
