<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a business Calendar Assignment definition.
 *
 * @package ProcessMaker\Model
 * 
 * @property integer CALENDAR_ASSIGNMENTS_ID
 * @property integer CALENDAR_ID
 * @property string OBJECT_UID
 * @property string OBJECT_TYPE
 * @property string CALENDAR_UID
 */
class CalendarAssignment extends Model
{
    protected $table = 'CALENDAR_ASSIGNMENTS';
    protected $primaryKey = 'CALENDAR_ASSIGNMENTS_ID';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'CALENDAR_ID',
        'OBJECT_UID',
        'OBJECT_TYPE',
        'CALENDAR_UID'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'CALENDAR_UID' => null,
        'OBJECT_UID' => '',
        'OBJECT_TYPE' => null,
        'CALENDAR_UID' => null
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'CALENDAR_ID' => 'integer',
        'OBJECT_UID' => 'string',
        'OBJECT_TYPE' => 'string',
        'CALENDAR_UID' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'OBJECT_UID' => 'required|max:32',
        'OBJECT_TYPE' => 'required|max:100',
        'CALENDAR_UID' => 'required|max:32'
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
