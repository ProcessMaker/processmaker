<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represent a variable of a process
 *
 * @package ProcessMaker\Model
 */
class ProcessVariable extends Model
{
    use ValidatingTrait,
        Uuid;

    const DEFAULT_DB_SOURCE_NAME = 'workflow';

    const VARIABLE_TYPES = ['string', 'integer', 'float', 'boolean', 'datetime',
    'grid', 'array', 'file', 'multiplefile', 'object'];

    // We do not store timestamps
    public $timestamps = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'field_type' => 'required|in:string,integer,float,boolean,datetime,grid,array,file,multiplefile,object',
        'field_size' => 'integer',
        'label' => 'required|string',
        'process_id' => 'nullable|exists:processes,id',
        'input_document_id' => 'nullable|exists:input_documents,id',
        'db_source_id' => 'nullable|exists:db_sources,id',
        'sql' => 'string|nullable',
        'null' => 'boolean|nullable'
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
        return 'uid';
    }

    /**
     * Process that owns the variable
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id', 'id');
    }

    /**
     * Associated database connection of the variable (it can be null)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dbSource()
    {
        return $this->hasOne(DbSource::class, 'id', 'db_source_id');
    }
}
