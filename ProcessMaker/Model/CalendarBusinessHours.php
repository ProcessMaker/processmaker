<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Represents a business Calendar Business Horus.
 *
 * @package ProcessMaker\Model
 *
 * @property integer CALENDAR_BUSINESS_ID
 * @property integer CALENDAR_ID
 * @property string CALENDAR_UID
 * @property string CALENDAR_BUSINESS_DAY
 * @property string CALENDAR_BUSINESS_START
 * @property string CALENDAR_BUSINESS_END
 *
 */
class CalendarBusinessHours extends Model
{
    use ValidatingTrait;

    protected $table = 'CALENDAR_BUSINESS_HOURS';
    protected $primaryKey = 'CALENDAR_BUSINESS_ID';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'CALENDAR_ID',
        'CALENDAR_UID',
        'CALENDAR_BUSINESS_DAY',
        'CALENDAR_BUSINESS_START',
        'CALENDAR_BUSINESS_END'
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
        'CALENDAR_ID' => 'integer',
        'CALENDAR_UID' => 'string',
        'CALENDAR_BUSINESS_DAY' => 'string',
        'CALENDAR_BUSINESS_START' => 'string',
        'CALENDAR_BUSINESS_END' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'CALENDAR_UID' => 'required|max:32',
        'CALENDAR_BUSINESS_DAY' => 'required|max:10',
        'CALENDAR_BUSINESS_START' => 'required|max:10',
        'CALENDAR_BUSINESS_END' => 'required|max:10'
    ];

    /**
     * Calendar definition
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function definition()
    {
        return $this->belongsTo(CalendarDefinition::class,'CALENDAR_ID', 'CALENDAR_ID');
    }

}