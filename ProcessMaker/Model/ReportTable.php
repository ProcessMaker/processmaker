<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Facades\SchemaManager;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of reportTable
 * @package ProcessMaker\Model
 */
class ReportTable extends Model
{
    use ValidatingTrait;

    // all tables will have this prefix
    const TABLE_PREFIX = 'PMT_';
    public static $attributesList = [
        'ADD_TAB_UID',
        'ADD_TAB_NAME',
        'ADD_TAB_DESCRIPTION',
        'ADD_TAB_PLG_UID',
        'DBS_UID',
        'PRO_UID',
        'ADD_TAB_TYPE',
        'ADD_TAB_GRID',
        'ADD_TAB_TAG'
    ];

    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'ADDITIONAL_TABLES';
    protected $primaryKey = 'ADD_TAB_UID';

    // validation rules
    protected $rules = [
        'ADD_TAB_NAME' => 'required',
        'ADD_TAB_DESCRIPTION' => 'required',
        'DBS_UID' => 'required',
        'ADD_TAB_TYPE' => 'required'
    ];

    // validation rules
    protected $appends = [
        'fields'
    ];

    /**
     * Returns the name of the physical table
     *
     * @return string
     */
    public function physicalTableName()
    {
        return ReportTable::TABLE_PREFIX . strtoupper($this->ADD_TAB_NAME);
    }

    /**
     * Returns all the data rows of the physical table
     *
     * @return array
     */
    public function allDataRows()
    {
        return $this->getAssociatedPmTable()->allDataRows();
    }

    /**
     * Eloquent getter that returns the fields of the report table
     *
     * @return mixed
     */
    public function getFieldsAttribute()
    {
        $pmTable = PmTable::where('ADD_TAB_ID', $this->ADD_TAB_ID)->first();
        $fieldsMeta = SchemaManager::getMetadataFromSchema($pmTable)->columns;
        return $fieldsMeta;
    }

    /**
     * Eloquent relation that return the associated process of the report table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'PRO_UID', 'PRO_UID');
    }

    /**
     * Eloquent relation that return the collection of variables associated to the report table
     *
     * @return $this
     */
    public function variables()
    {
        return $this->belongsToMany(
            ProcessVariable::class,
            'FIELDS',
            'ADD_TAB_UID',
            'VAR_ID'
        )
            ->withPivot('FLD_NAME');
    }

    /**
     * Returns the PmTable of the report table
     *
     * @return PmTable
     */
    public function getAssociatedPmTable()
    {
        return PmTable::whereAddTabUid($this->ADD_TAB_UID)->first();
    }
}
