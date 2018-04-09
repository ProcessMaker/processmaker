<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Task
 *
 * @property int TAS_ID
 * @property text TAS_TITLE
 * @property text TAS_PRIORITY_VARIABLE
 * @property string TAS_DELAY_TYPE
 * @property string PRO_UID
 * @property string STG_UID
 * @property string TAS_BOUNDARY
 * @property string TAS_COLOR
 * @property string TAS_EVN_UID
 * @property string TAS_OWNER_APP
 * @property string TAS_UID
 * @property text TAS_DEF_DESCRIPTION
 * @property text TAS_DEF_MESSAGE
 * @property text TAS_DEF_PROC_CODE
 * @property text TAS_DEF_SUBJECT_MESSAGE
 * @property text TAS_DEF_TITLE
 * @property text TAS_DESCRIPTION
 * @property text TAS_RECEIVE_MESSAGE
 * @property text TAS_RECEIVE_SUBJECT_MESSAGE
 * @property string TAS_GROUP_VARIABLE
 * @property string TAS_DERIVATION_SCREEN_TPL
 * @property string TAS_SELFSERVICE_TIME_UNIT
 * @property string TAS_EMAIL_SERVER_UID
 * @property string TAS_RECEIVE_SERVER_UID
 * @property string TAS_SELFSERVICE_TRIGGER_UID
 * @property float TAS_DURATION
 * @property float TAS_TEMPORIZER
 * @property string TAS_POSX
 * @property string TAS_POSY
 * @property int TAS_LAST_ASSIGNED
 * @property int TAS_USER
 * @property int TAS_NOT_EMAIL_FROM_FORMAT
 * @property int TAS_RECEIVE_EMAIL_FROM_FORMAT
 * @property int TAS_SELFSERVICE_TIME
 * @property int TAS_SELFSERVICE_TIMEOUT
 * @property int TAS_TYPE_DAY
 * @property int TAS_WIDTH
 * @property int TAS_HEIGHT
 * @property string TAS_RECEIVE_MESSAGE_TEMPLATE
 * @property string TAS_ASSIGN_TYPE
 * @property string TAS_TIMEUNIT
 * @property string TAS_SELFSERVICE_EXECUTION
 * @property string TAS_ALERT
 * @property string TAS_ASSIGN_LOCATION
 * @property string TAS_ASSIGN_LOCATION_ADHOC
 * @property string TAS_AUTO_ROOT
 * @property string TAS_CAN_CANCEL
 * @property string TAS_CAN_DELETE_DOCS
 * @property string TAS_CAN_PAUSE
 * @property string TAS_CAN_UPLOAD
 * @property string TAS_OFFLINE
 * @property string TAS_RECEIVE_LAST_EMAIL
 * @property string TAS_SELF_SERVICE
 * @property string TAS_START
 * @property string TAS_TO_LAST_USER
 * @property string TAS_TRANSFER_FLY
 * @property string TAS_VIEW_ADDITIONAL_DOCUMENTATION
 * @property string TAS_VIEW_UPLOAD
 * @property string TAS_DERIVATION
 * @property string TAS_TYPE
 * @property string TAS_ASSIGN_VARIABLE
 * @property string TAS_MI_INSTANCE_VARIABLE
 * @property string TAS_MI_COMPLETE_VARIABLE
 * @property string TAS_RECEIVE_MESSAGE_TYPE
 * @property string TAS_CAN_SEND_MESSAGE
 * @property string TAS_SEND_LAST_EMAIL'
 *
 */
class Task extends Model
{

    use Notifiable, ValidatingTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'TASK';
    protected $primaryKey = 'TAS_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = null;

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'TAS_ID',
        'TAS_TITLE',
        'TAS_PRIORITY_VARIABLE',
        'TAS_DELAY_TYPE',
        'PRO_UID',
        'STG_UID',
        'TAS_BOUNDARY',
        'TAS_COLOR',
        'TAS_EVN_UID',
        'TAS_OWNER_APP',
        'TAS_UID',
        'TAS_DEF_DESCRIPTION',
        'TAS_DEF_MESSAGE',
        'TAS_DEF_PROC_CODE',
        'TAS_DEF_SUBJECT_MESSAGE',
        'TAS_DEF_TITLE',
        'TAS_DESCRIPTION',
        'TAS_RECEIVE_MESSAGE',
        'TAS_RECEIVE_SUBJECT_MESSAGE',
        'TAS_GROUP_VARIABLE',
        'TAS_DERIVATION_SCREEN_TPL',
        'TAS_SELFSERVICE_TIME_UNIT',
        'TAS_EMAIL_SERVER_UID',
        'TAS_RECEIVE_SERVER_UID',
        'TAS_SELFSERVICE_TRIGGER_UID',
        'TAS_DURATION',
        'TAS_TEMPORIZER',
        'TAS_POSX',
        'TAS_POSY',
        'TAS_LAST_ASSIGNED',
        'TAS_USER',
        'TAS_NOT_EMAIL_FROM_FORMAT',
        'TAS_RECEIVE_EMAIL_FROM_FORMAT',
        'TAS_SELFSERVICE_TIME',
        'TAS_SELFSERVICE_TIMEOUT',
        'TAS_TYPE_DAY',
        'TAS_WIDTH',
        'TAS_HEIGHT',
        'TAS_RECEIVE_MESSAGE_TEMPLATE',
        'TAS_ASSIGN_TYPE',
        'TAS_TIMEUNIT',
        'TAS_SELFSERVICE_EXECUTION',
        'TAS_ALERT',
        'TAS_ASSIGN_LOCATION',
        'TAS_ASSIGN_LOCATION_ADHOC',
        'TAS_AUTO_ROOT',
        'TAS_CAN_CANCEL',
        'TAS_CAN_DELETE_DOCS',
        'TAS_CAN_PAUSE',
        'TAS_CAN_UPLOAD',
        'TAS_OFFLINE',
        'TAS_RECEIVE_LAST_EMAIL',
        'TAS_SELF_SERVICE',
        'TAS_START',
        'TAS_TO_LAST_USER',
        'TAS_TRANSFER_FLY',
        'TAS_VIEW_ADDITIONAL_DOCUMENTATION',
        'TAS_VIEW_UPLOAD',
        'TAS_DERIVATION',
        'TAS_TYPE',
        'TAS_ASSIGN_VARIABLE',
        'TAS_MI_INSTANCE_VARIABLE',
        'TAS_MI_COMPLETE_VARIABLE',
        'TAS_RECEIVE_MESSAGE_TYPE',
        'TAS_CAN_SEND_MESSAGE',
        'TAS_SEND_LAST_EMAIL'
    ];
    protected $attributes = [
        'PRO_UID' => '',
        'TAS_UID' => '',
        'TAS_ID' => null,
        'TAS_TITLE' => '',
        'TAS_DESCRIPTION' => '',
        'TAS_TYPE' => 'NORMAL',
        'TAS_DURATION' => 0,
    ];
    protected $casts = [
        'PRO_UID' => 'string',
        'TAS_UID' => 'string',
        'TAS_ID' => 'int',
        'TAS_TITLE' => 'string',
        'TAS_DESCRIPTION' => 'string',
        'TAS_TYPE' => 'string',
        'TAS_DURATION' => 'float',
    ];

    protected $rules = [
        'PRO_UID' => 'required|max:32',
        'TAS_UID' => 'required|max:32',
        'TAS_TITLE' => 'required',
        'TAS_DESCRIPTION' => 'required',
        'TAS_TYPE' => 'required',
        'TAS_DURATION' => 'required',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'TAS_UID';
    }
}
