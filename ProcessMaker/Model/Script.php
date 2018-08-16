<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Script
 *
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property string title
 * @property string description
 * @property int process_id
 * @property string type
 * @property text webbot
 * @property array param
 *
 */
class Script extends Model
{
    use ValidatingTrait;
    use Uuid;

    protected $table = 'scripts';

    const SCRIPT_TYPE = 'SCRIPT';

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
        'type' => self::SCRIPT_TYPE,
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
        'process_id' => 'exists:processes,id',
        'type' => 'required|in:' . self::SCRIPT_TYPE
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
    public function setParamAttribute($value)
    {
        $this->attributes['param'] = empty($value) ? null : json_encode($value);
    }

}
