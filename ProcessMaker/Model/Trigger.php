<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Trigger
 *
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property text title
 * @property text description
 * @property int process_id
 * @property string type
 * @property text webbot
 * @property array param
 *
 */
class Trigger extends Model
{
    use ValidatingTrait;
    use Uuid;

    protected $table = 'triggers';

    const TRIGGER_TYPE = 'SCRIPT';

    protected $fillable = [
        'uid',
        'title',
        'description',
        'process_id',
        'type',
        'webbot',
        'param'
    ];

    protected $attributes = [
        'uid' => null,
        'title' => '',
        'description' => '',
        'process_id' => '',
        'type' => self::TRIGGER_TYPE,
        'webbot' => '',
        'param' => ''
    ];
    protected $casts = [
        'uid' => 'string',
        'title' => 'string',
        'description' => 'string',
        'process_id' => 'int',
        'type' => 'string',
        'webbot' => 'string',
        'param' => 'string'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'title' => 'required|unique:triggers,title',
        'process_id' => 'exists:processes,id',
        'type' => 'required|in:' . self::TRIGGER_TYPE
    ];

    protected $validationMessages = [
        'title.unique' => 'A trigger with the same name already exists in this process.'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Get the process we belong to.
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Accessor param to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getParamAttribute($value): ?array
    {
        return json_decode($value);
    }

    /**
     * Mutator param json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setParamAttribute($value): void
    {
        $this->attributes['param'] = empty($value) ? null : json_encode($value);
    }

}
