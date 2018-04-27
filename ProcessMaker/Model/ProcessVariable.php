<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Represent a variable of a process
 *
 * @package ProcessMaker\Model
 */
class ProcessVariable extends Model
{
    use ValidatingTrait;

    const DEFAULT_DB_SOURCE_NAME = 'workflow';

    const VARIABLE_TYPES = ['string', 'integer', 'float', 'boolean', 'datetime',
    'grid', 'array', 'file', 'multiplefile', 'object'];

    // We do not store timestamps
    public $timestamps = false;
    protected $table = 'PROCESS_VARIABLES';
    protected $primaryKey = 'VAR_ID';
    public $incrementing = false;

    protected $rules = [
        'VAR_NAME' => 'required|string|max:255',
        'VAR_FIELD_TYPE' => 'required|in:string,integer,float,boolean,datetime,grid,array,file,multiplefile,object',
        'VAR_FIELD_SIZE' => 'integer',
        'VAR_LABEL' => 'required|string',
        'VAR_DBCONNECTION' => 'string|nullable',
        'VAR_SQL' => 'string|nullable',
        'VAR_NULL' => 'boolean|nullable'
    ];

    protected $appends = [
        'VAR_DBCONNECTION_LABEL',
    ];

    /**
     * Returns the label to show for the associated database connection
     *
     * @return string
     */
    public function getVarDbconnectionLabelAttribute()
    {
        $result = ($this->dbSource !== null)
            ? '[' . $this->dbSource->server . ':' .
            $this->dbSource->port . '] ' .
            $this->dbSource->type . ': ' .
            $this->dbSource->database_name
            : ProcessVariable::DEFAULT_DB_SOURCE_NAME;
        return $result;
    }

    /**
     * Key to be used in http routes.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'VAR_UID';
    }

    /**
     * Process that owns the variable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'PRO_ID', 'PRO_ID');
    }

    /**
     * Associated database connection of the variable (it can be null)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbSource()
    {
        return $this->hasOne(DbSource::class, 'uid', 'VAR_DBCONNECTION');
    }
}
