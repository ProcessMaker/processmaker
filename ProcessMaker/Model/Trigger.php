<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Trigger
 *
 * @package ProcessMaker\Model
 *
 * @property int TRI_ID
 * @property string TRI_UID
 * @property text TRI_TITLE
 * @property text TRI_DESCRIPTION
 * @property int PRO_ID
 * @property string PRO_UID
 * @property string TRI_TYPE
 * @property text TRI_WEBBOT
 * @property text TRI_PARAM
 *
 */
class Trigger extends Model
{
    use ValidatingTrait;

    protected $table = 'TRIGGERS';
    protected $primaryKey = 'TRI_ID';

    const TRIGGER_TYPE = 'SCRIPT';

    public $timestamps = false;

    protected $fillable = [
        'TRI_UID',
        'TRI_TITLE',
        'TRI_DESCRIPTION',
        'PRO_ID',
        'PRO_UID',
        'TRI_TYPE',
        'TRI_WEBBOT',
        'TRI_PARAM'
    ];

    protected $attributes = [
        'TRI_UID' => null,
        'TRI_TITLE' => '',
        'TRI_DESCRIPTION' => '',
        'PRO_ID' => '',
        'PRO_UID' => null,
        'TRI_TYPE' => self::TRIGGER_TYPE,
        'TRI_WEBBOT' => '',
        'TRI_PARAM' => ''
    ];
    protected $casts = [
        'TRI_UID' => 'string',
        'TRI_TITLE' => 'string',
        'TRI_DESCRIPTION' => 'string',
        'PRO_ID' => 'int',
        'PRO_UID' => 'string',
        'TRI_TYPE' => 'string',
        'TRI_WEBBOT' => 'string',
        'TRI_PARAM' => 'string'
    ];

    protected $rules = [
        'TRI_UID' => 'required|max:32',
        'TRI_TITLE' => 'required|unique:TRIGGERS,TRI_TITLE',
        'PRO_ID' => 'required',
        'PRO_UID' => 'required|max:32',
        'TRI_TYPE' => 'required|in:' . self::TRIGGER_TYPE
    ];

    protected $validationMessages = [
        'TRI_TITLE.unique' => 'A trigger with the same name already exists in this process.'
    ];

    /**
     * Get the process we belong to.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

}
