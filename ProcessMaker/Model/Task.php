<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

/**
 * Represents an Eloquent model of a task
 *
 * @property int id
 * @property string uid
 * @property int process_id
 * @property text title
 * @property text priority_variable
 * @property string delay_type
 * @property string stg_uid
 * @property string boundary
 * @property string color
 * @property string evn_uid
 * @property string owner_app
 * @property text def_description
 * @property text def_message
 * @property text def_proc_code
 * @property text def_subject_message
 * @property text def_title
 * @property text description
 * @property text receive_message
 * @property text receive_subject_message
 * @property string group_variable
 * @property string derivation_screen_tpl
 * @property string selfservice_time_unit
 * @property string email_server_uid
 * @property string receive_server_uid
 * @property string selfservice_trigger_uid
 * @property float duration
 * @property float temporizer
 * @property string posx
 * @property string posy
 * @property int last_assigned
 * @property int user
 * @property int not_email_from_format
 * @property int receive_email_from_format
 * @property int selfservice_time
 * @property int selfservice_timeout
 * @property int type_day
 * @property int width
 * @property int height
 * @property string receive_message_template
 * @property string assign_type
 * @property string timeunit
 * @property string selfservice_execution
 * @property string alert
 * @property string assign_location
 * @property string assign_location_adhoc
 * @property string auto_root
 * @property string can_cancel
 * @property string can_delete_docs
 * @property string can_pause
 * @property string can_upload
 * @property string offline
 * @property string receive_last_email
 * @property string self_service
 * @property string start
 * @property string to_last_user
 * @property string transfer_fly
 * @property string view_additional_documentation
 * @property string view_upload
 * @property string derivation
 * @property string type
 * @property string assign_variable
 * @property string mi_instance_variable
 * @property string mi_complete_variable
 * @property string receive_message_type
 * @property string can_send_message
 * @property string send_last_email
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

    protected $fillable = [
        'id',
        'uid',
        'process_id',
        'title',
        'priority_variable',
        'delay_type',
        'stg_uid',
        'boundary',
        'color',
        'evn_uid',
        'owner_app',
        'uid',
        'def_description',
        'def_message',
        'def_proc_code',
        'def_subject_message',
        'def_title',
        'description',
        'receive_message',
        'receive_subject_message',
        'group_variable',
        'derivation_screen_tpl',
        'selfservice_time_unit',
        'email_server_uid',
        'receive_server_uid',
        'selfservice_trigger_uid',
        'duration',
        'temporizer',
        'posx',
        'posy',
        'last_assigned',
        'user',
        'not_email_from_format',
        'receive_email_from_format',
        'selfservice_time',
        'selfservice_timeout',
        'type_day',
        'width',
        'height',
        'receive_message_template',
        'assign_type',
        'timeunit',
        'selfservice_execution',
        'alert',
        'assign_location',
        'assign_location_adhoc',
        'auto_root',
        'can_cancel',
        'can_delete_docs',
        'can_pause',
        'can_upload',
        'offline',
        'receive_last_email',
        'self_service',
        'start',
        'to_last_user',
        'transfer_fly',
        'view_additional_documentation',
        'view_upload',
        'derivation',
        'type',
        'assign_variable',
        'mi_instance_variable',
        'mi_complete_variable',
        'receive_message_type',
        'can_send_message',
        'send_last_email'
    ];
    protected $attributes = [
        'id' => null,
        'uid' => '',
        'process_id' => '',
        'title' => '',
        'description' => '',
        'type' => 'normal',
        'duration' => 0,
    ];
    protected $casts = [
        'id' => 'int',
        'process_id' => 'int',
        'uid' => 'string',
        'title' => 'string',
        'description' => 'string',
        'type' => 'string',
        'duration' => 'float',
    ];

    protected $rules = [
        'uid' => 'max:36',
        'process_id' => 'exists:processes,id',
        'title' => 'required|unique:tasks,title',
        'description' => 'required',
        'type' => 'required',
        'duration' => 'required',
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
     * List of users assigned to task
     *
     * @return MorphToManyCustom
     */
    /*public function usersAssigned(): MorphToMany
    {
        return $this->morphedByManyCustom(User::class, 'assignee', 'task_user', 'id', 'USR_ID', null, null, 'TU_RELATION');
    }*/

    /**
     * List of groups assigned to task
     *
     * @return MorphToManyCustom
     */
    /*public function groupsAssigned():MorphToManyCustom
    {
        return $this->morphedByManyCustom(Group::class, 'assignee', 'task_user', 'id', 'USR_ID', null, null, 'TU_RELATION');
    }*/

}
