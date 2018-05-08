<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Facades\SchemaManager;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of reportTable
 * @package ProcessMaker\Model
 */
class ReportTable extends Model
{
    use ValidatingTrait;
    use Uuid;

    // all tables will have this prefix
    const TABLE_PREFIX = 'PMT_';

    public $timestamps = false;

    protected $table = 'additional_tables';

    // validation rules
    protected $rules = [
        'name' => 'required',
        'description' => 'required',
        'type' => 'required'
    ];

    // validation rules
    protected $appends = [
        'fields'
    ];

    /*
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }



    /**
     * Returns the name of the physical table
     *
     * @return string
     */
    public function physicalTableName()
    {
        return ReportTable::TABLE_PREFIX . strtoupper($this->name);
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
        $pmTable = PmTable::where('id', $this->id)->first();
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
        return $this->belongsTo(Process::class);
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
            'report_table_columns',
            'report_table_id',
            'process_variable_id'
        )
            ->withPivot('name');
    }

    /**
     * Returns the PmTable of the report table
     *
     * @return PmTable
     */
    public function getAssociatedPmTable()
    {
        return PmTable::where('id', $this->id)->first();
    }
}
