<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

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

    use Notifiable, Uuid;

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'tasks';
    
    //Task type
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_ADHOC = 'ADHOC';
    const TYPE_SUB_PROCESS = 'SUBPROCESS';
    const TYPE_HIDDEN = 'HIDDEN';
    const TYPE_GATEWAY = 'GATEWAYTOGATEWAY';
    const TYPE_WEB_ENTRY_EVENT = 'WEBENTRYEVENT';
    const TYPE_END_MESSAGE_EVENT = 'END-MESSAGE-EVENT';
    const TYPE_START_MESSAGE_EVENT = 'START-MESSAGE-EVENT';
    const TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT = 'INTERMEDIATE-THROW-MESSAGE-EVENT';
    const TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT = 'INTERMEDIATE-CATCH-MESSAGE-EVENT';
    const TYPE_SCRIPT_TASK = 'SCRIPT-TASK';
    const TYPE_START_TIMER_EVENT = 'START-TIMER-EVENT';
    const TYPE_INTERMEDIATE_CATCH_TIMER_EVENT = 'INTERMEDIATE-CATCH-TIMER-EVENT';
    const TYPE_END_EMAIL_EVENT = 'END-EMAIL-EVENT';
    const TYPE_INTERMEDIATE_THROW_EMAIL_EVENT = 'INTERMEDIATE-THROW-EMAIL-EVENT';
    const TYPE_SERVICE_TASK = 'SERVICE-TASK';

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
        'process_id' => 'exists:processes,id',
        'self_service_trigger_id' => 'sometimes|exists:triggers,id',
        'title' => 'required|unique:tasks,title',
        'description' => 'required',
        'type' => 'required',
        'type',
        'assign_type',
        'routing_type',
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

}
