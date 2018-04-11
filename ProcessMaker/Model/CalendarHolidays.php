<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Represents a business Calendar Business Horus.
 *
 * @package ProcessMaker\Model
 *
 * @property integer CALENDAR_HOLIDAYS_ID
 * @property integer CALENDAR_ID
 * @property string CALENDAR_UID
 * @property string CALENDAR_HOLIDAY_NAME
 * @property string CALENDAR_HOLIDAY_START
 * @property string CALENDAR_HOLIDAY_END
 *
 */
class CalendarHolidays extends Model
{
    use ValidatingTrait;

    protected $table = 'CALENDAR_HOLIDAYS';
    protected $primaryKey = 'CALENDAR_HOLIDAYS_ID';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'CALENDAR_ID',
        'CALENDAR_UID',
        'CALENDAR_HOLIDAY_NAME',
        'CALENDAR_HOLIDAY_START',
        'CALENDAR_HOLIDAY_END'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'CALENDAR_ID' => '',
        'CALENDAR_UID' => '',
        'CALENDAR_HOLIDAY_NAME' => '',
        'CALENDAR_HOLIDAY_START' => '',
        'CALENDAR_HOLIDAY_END' => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'CALENDAR_UID' => 'string',
        'CALENDAR_ID' => 'integer',
        'CALENDAR_HOLIDAY_NAME' => 'string',
        'CALENDAR_HOLIDAY_START' => 'string',
        'CALENDAR_HOLIDAY_END' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'CALENDAR_UID' => 'required|max:32',
        'CALENDAR_HOLIDAY_NAME' => 'required|max:100',
        'CALENDAR_HOLIDAY_START' => 'required',
        'CALENDAR_HOLIDAY_END' => 'required'
    ];

    /**
     * Calendar definition
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function definition()
    {
        return $this->belongsTo(CalendarDefinition::class, 'CALENDAR_ID', 'CALENDAR_ID');
    }

}
