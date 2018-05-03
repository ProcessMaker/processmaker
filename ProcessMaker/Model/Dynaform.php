<?php

namespace ProcessMaker\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Class dynaform
 * @package ProcessMaker\Model
 *
 * @property int DYN_ID
 * @property string DYN_UID
 * @property int PRO_ID
 * @property string PRO_UID
 * @property string DYN_TITLE
 * @property string DYN_DESCRIPTION
 * @property string DYN_TYPE
 * @property array DYN_CONTENT
 * @property string DYN_LABEL
 * @property Carbon DYN_UPDATE_DATE
 *
 */
class Dynaform extends Model
{
    use ValidatingTrait;

    protected $table = 'DYNAFORM';
    protected $primaryKey = 'DYN_ID';

    const CREATED_AT = null;

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'DYN_UPDATE_DATE';


    /**
     * Values for DYN_TYPE
     */
    const TYPE = [
        'FORM',
        'GRID'
    ];

    protected $fillable = [
        'DYN_UID',
        'PRO_ID',
        'PRO_UID',
        'DYN_TITLE',
        'DYN_DESCRIPTION',
        'DYN_TYPE',
        'DYN_CONTENT',
        'DYN_LABEL',
        'DYN_UPDATE_DATE'
    ];

    protected $attributes = [
        'DYN_UID' => null,
        'PRO_ID' => '',
        'PRO_UID' => null,
        'DYN_TITLE' => null,
        'DYN_DESCRIPTION' => null,
        'DYN_TYPE' => 'FORM',
        'DYN_CONTENT' => null,
        'DYN_LABEL' => null,
        'DYN_UPDATE_DATE' => null,
    ];

    protected $casts = [
        'DYN_UID' => 'string',
        'PRO_ID' => 'int',
        'PRO_UID' => 'string',
        'DYN_TITLE' => 'string',
        'DYN_DESCRIPTION' => 'string',
        'DYN_TYPE' => 'string',
        'DYN_CONTENT' => 'array',
        'DYN_LABEL' => 'string',
        'DYN_UPDATE_DATE' => 'string',
    ];

    protected $rules = [
        'DYN_UID' => 'required|max:32',
        'PRO_ID' => 'required',
        'PRO_UID' => 'required|max:32',
        'DYN_TITLE' => 'required|unique:DYNAFORM,DYN_TITLE',
        'DYN_TYPE' => 'required'
    ];

    protected $validationMessages = [
        'DYN_TITLE.unique' => 'A Dynaform with the same name already exists in this process.'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'DYN_UID';
    }

    /**
     * Accessor DYN_CONTENT to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getDynContentAttribute($value): ?array
    {
        return json_decode($value, true);
    }

    /**
     * Mutator DYN_CONTENT json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setDynContentAttribute($value): void
    {
        $this->attributes['DYN_CONTENT'] = empty($value) ? null : json_encode($value);
    }

}
