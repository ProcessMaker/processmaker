<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a configuration parameter in the system for persistence.
 * See: \ProcessMaker\Config\Repository
 */
class Configuration extends Model
{
    protected $fillable = [
        'parameter',
        'value'
    ];

    public $timestamps = false;
}
