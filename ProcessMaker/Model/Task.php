<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a task
 *
 * @property integer id
 * @property string uid
 * @property integer process_id
 * @property string title
 * @property string description
 * @property string type
 * @property string assign_type
 * @property string routing_type
 * @property string priority_variable
 * @property string assign_variable
 * @property string group_variable
 * @property boolean is_start_task
 * @property string routing_screen_template
 * @property array timing_control_configuration
 * @property integer self_service_trigger_id
 * @property array self_service_timeout_configuration
 * @property string custom_title
 * @property string custom_description
 *
 */
class Task extends Model
{

    use Notifiable,
        ValidatingTrait,
        Uuid;

    //Task types
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_ADHOC = 'ADHOC';
    const TYPE_SUB_PROCESS = 'SUB_PROCESS';
    const TYPE_HIDDEN = 'HIDDEN';
    const TYPE_GATEWAY = 'GATEWAY_TO_GATEWAY';
    const TYPE_WEB_ENTRY_EVENT = 'WEB_ENTRY_EVENT';
    const TYPE_END_MESSAGE_EVENT = 'END_MESSAGE_EVENT';
    const TYPE_START_MESSAGE_EVENT = 'START_MESSAGE_EVENT';
    const TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT = 'INTERMEDIATE_THROW_MESSAGE_EVENT';
    const TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT = 'INTERMEDIATE_CATCH_MESSAGE_EVENT';
    const TYPE_SCRIPT_TASK = 'SCRIPT_TASK';
    const TYPE_START_TIMER_EVENT = 'START_TIMER_EVENT';
    const TYPE_INTERMEDIATE_CATCH_TIMER_EVENT = 'INTERMEDIATE_CATCH_TIMER_EVENT';
    const TYPE_END_EMAIL_EVENT = 'END_EMAIL_EVENT';
    const TYPE_INTERMEDIATE_THROW_EMAIL_EVENT = 'INTERMEDIATE_THROW_EMAIL_EVENT';
    const TYPE_SERVICE_TASK = 'SERVICE_TASK';

    //Assign types
    const ASSIGN_TYPE_BALANCED = 'BALANCED';
    const ASSIGN_TYPE_MANUAL = 'MANUAL';
    const ASSIGN_TYPE_EVALUATE = 'EVALUATE';
    const ASSIGN_TYPE_REPORT_TO = 'REPORT_TO';
    const ASSIGN_TYPE_SELF_SERVICE = 'SELF_SERVICE';
    const ASSIGN_TYPE_STATIC_MI = 'STATIC_MI';
    const ASSIGN_TYPE_CANCEL_MI = 'CANCEL_MI';
    const ASSIGN_TYPE_MULTIPLE_INSTANCE = 'MULTIPLE_INSTANCE';
    const ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED = 'MULTIPLE_INSTANCE_VALUE_BASED';

    //Routing types
    const ROUTE_TYPE_NORMAL = 'NORMAL';
    const ROUTE_TYPE_FAST = 'FAST';
    const ROUTE_TYPE_AUTOMATIC = 'AUTOMATIC';

    //Day types
    const WORK_DAYS = 'WORK_DAYS';
    const CALENDAR_DAYS = 'CALENDAR_DAYS';

    //Unit Time Types
    const TIME_MINUTES = 'MINUTES';
    const TIME_HOURS = 'HOURS';
    const TIME_DAYS = 'DAYS';
    const TIME_WEEKS = 'WEEKS';
    const TIME_MONTHS = 'MONTHS';

    //Service execution types
    const EXECUTION_EVERY_TIME = 'EVERY_TIME';
    const EXECUTION_ONCE = 'ONCE';

    protected $fillable = [
        'uid',
        'process_id',
        'title',
        'description',
        'type',
        'assign_type',
        'routing_type',
        'priority_variable',
        'assign_variable',
        'group_variable',
        'is_start_task',
        'routing_screen_template',
        'timing_control_configuration',
        'self_service_trigger_id',
        'self_service_timeout_configuration',
        'custom_title',
        'custom_description',
        'created_at',
        'updated_at'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'title' => 'required|unique:tasks,title',
        'description' => 'required',
        'process_id' => 'exists:processes,id',
        'self_service_trigger_id' => 'nullable|exists:triggers,id',
        'type' => 'required|in:' . self::TYPE_NORMAL . ',' . self::TYPE_ADHOC . ',' . self::TYPE_SUB_PROCESS . ',' . self::TYPE_HIDDEN . ',' . self::TYPE_GATEWAY . ',' . self::TYPE_WEB_ENTRY_EVENT . ',' . self::TYPE_END_MESSAGE_EVENT . ',' . self::TYPE_START_MESSAGE_EVENT . ',' . self::TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT . ',' . self::TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT . ',' . self::TYPE_SCRIPT_TASK . ',' . self::TYPE_START_TIMER_EVENT . ',' . self::TYPE_INTERMEDIATE_CATCH_TIMER_EVENT . ',' . self::TYPE_END_EMAIL_EVENT . ',' . self::TYPE_INTERMEDIATE_THROW_EMAIL_EVENT . ',' . self::TYPE_SERVICE_TASK,
        'assign_type' => 'required|in:' . self::ASSIGN_TYPE_BALANCED . ',' . self::ASSIGN_TYPE_MANUAL . ',' . self::ASSIGN_TYPE_EVALUATE . ',' . self::ASSIGN_TYPE_REPORT_TO . ',' . self::ASSIGN_TYPE_SELF_SERVICE . ',' . self::ASSIGN_TYPE_STATIC_MI . ',' . self::ASSIGN_TYPE_CANCEL_MI . ',' . self::ASSIGN_TYPE_MULTIPLE_INSTANCE . ',' . self::ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED,
        'routing_type' => 'required|in:' . self::ROUTE_TYPE_NORMAL . ',' . self::ROUTE_TYPE_FAST . ',' . self::ROUTE_TYPE_AUTOMATIC,
        'timing_control_configuration' => 'required|array',
        'timing_control_configuration.duration' => 'required|min:0',
        'timing_control_configuration.delay_type' => 'required|in:' . self::TIME_MINUTES . ',' . self::TIME_HOURS . ',' . self::TIME_DAYS,
        'timing_control_configuration.temporizer' => 'required|min:0',
        'timing_control_configuration.type_day' => 'required|in:' . self::WORK_DAYS . ',' . self::CALENDAR_DAYS,
        'timing_control_configuration.time_unit' => 'required|in:' . self::TIME_MINUTES . ',' . self::TIME_HOURS . ',' . self::TIME_DAYS . ',' . self::TIME_WEEKS . ',' . self::TIME_MONTHS,
        'self_service_timeout_configuration' => 'required|array',
        'self_service_timeout_configuration.self_service_timeout' => 'required|min:0',
        'self_service_timeout_configuration.self_service_time' => 'required|min:0',
        'self_service_timeout_configuration.self_service_time_unit' => 'required|in:' . self::TIME_MINUTES . ',' . self::TIME_HOURS . ',' . self::TIME_DAYS . ',' . self::TIME_WEEKS . ',' . self::TIME_MONTHS,
        'self_service_timeout_configuration.self_service_execution' => 'required|in:' . self::EXECUTION_EVERY_TIME . ',' . self::EXECUTION_ONCE,
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
     * Accessor field timing_control_configuration
     *
     * @param $value
     *
     * @return array|null
     */
    public function getTimingControlConfigurationAttribute($value): ?array
    {
        $value = json_decode($value, true);
        $value['duration'] = isset($value['duration']) ? $value['duration'] : 0;
        $value['delay_type'] = isset($value['delay_type']) ? $value['delay_type'] : 'DAYS';
        $value['temporizer'] = isset($value['temporizer']) ? $value['temporizer'] : 0;
        $value['type_day'] = isset($value['type_day']) ? $value['type_day'] : 'WORK_DAYS';
        $value['time_unit'] = isset($value['time_unit']) ? $value['time_unit'] : 'DAYS';
        return $value;
    }

    /**
     * Mutator field timing_control_configuration
     *
     * @param $value
     *
     * @return void
     */
    public function setTimingControlConfigurationAttribute($value)
    {
        $value['duration'] = isset($value['duration']) ? $value['duration'] : 0;
        $value['delay_type'] = isset($value['delay_type']) ? $value['delay_type'] : 'DAYS';
        $value['temporizer'] = isset($value['temporizer']) ? $value['temporizer'] : 0;
        $value['type_day'] = isset($value['type_day']) ? $value['type_day'] : 'WORK_DAYS';
        $value['time_unit'] = isset($value['time_unit']) ? $value['time_unit'] : 'DAYS';
        $this->attributes['timing_control_configuration'] = empty($value) ? null : json_encode($value);
    }

    /**
     * Accessor field self_service_timeout_configuration
     *
     * @param $value
     *
     * @return array|null
     */
    public function getSelfServiceTimeoutConfigurationAttribute($value): ?array
    {
        $value = json_decode($value, true);
        $value['self_service_timeout'] = isset($value['self_service_timeout']) ? $value['self_service_timeout'] : 0;
        $value['self_service_time'] = isset($value['self_service_time']) ? $value['self_service_time'] : 0;
        $value['self_service_time_unit'] = isset($value['self_service_time_unit']) ? $value['self_service_time_unit'] : 'HOURS';
        $value['self_service_execution'] = isset($value['self_service_execution']) ? $value['self_service_execution'] : 'EVERY_TIME';
        return $value;
    }

    /**
     * Mutator field self_service_timeout_configuration
     *
     * @param $value
     *
     * @return void
     */
    public function setSelfServiceTimeoutConfigurationAttribute($value)
    {
        $value['self_service_timeout'] = isset($value['self_service_timeout']) ? $value['self_service_timeout'] : 0;
        $value['self_service_time'] = isset($value['self_service_time']) ? $value['self_service_time'] : 0;
        $value['self_service_time_unit'] = isset($value['self_service_time_unit']) ? $value['self_service_time_unit'] : 'HOURS';
        $value['self_service_execution'] = isset($value['self_service_execution']) ? $value['self_service_execution'] : 'EVERY_TIME';
        $this->attributes['self_service_timeout_configuration'] = empty($value) ? null : json_encode($value);
    }

}
