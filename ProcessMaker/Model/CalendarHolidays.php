<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a business Calendar Business Horus.
 *
 * @package ProcessMaker\Model
 *
 * @property integer CALENDAR_HOLIDAYS_ID
 * @property integer CALENDAR_ID
 * @property string CALENDAR_UID
 * @property string CALENDAR_BUSINESS_DAY
 * @property string CALENDAR_BUSINESS_START
 * @property string CALENDAR_BUSINESS_END
 *
 */
class CalendarHolidays extends Model
{
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
        'CALENDAR_BUSINESS_DAY' => '',
        'CALENDAR_BUSINESS_START' => '',
        'CALENDAR_BUSINESS_END' => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'CALENDAR_UID' => 'string',
        'CALENDAR_ID' => 'integer',
        'CALENDAR_BUSINESS_DAY' => 'string',
        'CALENDAR_BUSINESS_START' => 'string',
        'CALENDAR_BUSINESS_END' => 'string'
    ];

    /**
     * Calendar definition
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function definition()
    {
        return $this->belongsTo('CalendarDefinition');
        
    }

}