<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Represents a business Calendar Definition.
 *
 * @package ProcessMaker\Model
 *
 * @property integer CALENDAR_ID
 * @property string CALENDAR_UID
 * @property string CALENDAR_WORK_DAYS
 * @property string CALENDAR_NAME
 * @property string CALENDAR_DESCRIPTION
 * @property \Carbon\Carbon CALENDAR_CREATE_DATE
 * @property \Carbon\Carbon CALENDAR_UPDATE_DATE
 * @property string CALENDAR_STATUS
 *
 */
class CalendarDefinition extends Model
{
    use ValidatingTrait;

    protected $table = 'CALENDAR_DEFINITION';
    protected $primaryKey = 'CALENDAR_ID';

    const CREATED_AT = 'CALENDAR_CREATE_DATE';
    const UPDATED_AT = 'CALENDAR_UPDATE_DATE';

    public $timestamps = true;

    //valid status types
    const STATUS = 'ACTIVE,INACTIVE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'CALENDAR_UID',
        'CALENDAR_WORK_DAYS',
        'CALENDAR_NAME',
        'CALENDAR_DESCRIPTION',
        'CALENDAR_STATUS'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'CALENDAR_UID' => '',
        'CALENDAR_WORK_DAYS' => '',
        'CALENDAR_NAME' => '',
        'CALENDAR_DESCRIPTION' => '',
        'CALENDAR_STATUS' => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'CALENDAR_UID' => 'string',
        'CALENDAR_WORK_DAYS' => 'string',
        'CALENDAR_NAME' => 'string',
        'CALENDAR_DESCRIPTION' => 'string',
        'CALENDAR_CREATE_DATE' => 'datetime',
        'CALENDAR_UPDATE_DATE' => 'datetime',
        'CALENDAR_STATUS' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'CALENDAR_UID' => 'required|max:32',
        'CALENDAR_WORK_DAYS' => 'required|max:100',
        'CALENDAR_NAME' => 'required|max:32',
        'CALENDAR_STATUS' => 'required|max:8|in:' . self::STATUS
    ];

    /**
     * Calendar Holidays
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function holidays()
    {
        return $this->hasMany(CalendarHolidays::class,'CALENDAR_ID', 'CALENDAR_ID');
    }

    /**
     * Calendar Business Horus
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessHours()
    {
        return $this->hasMany(CalendarBusinessHours::class,'CALENDAR_ID', 'CALENDAR_ID');
    }

    /**
     * Calendar Assignment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignment()
    {
        return $this->hasMany(CalendarAssignment::class,'CALENDAR_ID', 'CALENDAR_ID');
    }

}
