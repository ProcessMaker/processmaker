<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

/**
 * Represents an Eloquent model of a task Notification
 *
 * @property integer id
 * @property string uid
 * @property integer task_id
 * @property integer email_server_id
 * @property string type
 * @property boolean last_email
 * @property boolean email_from_format
 * @property string message_subject
 * @property string message
 * @property string message_template
 * @property string message_type
 *
 */
class TaskNotification extends Model
{

    use Notifiable,
        Uuid;

    //Notification types
    const TYPE_AFTER_ROUTING = 'AFTER_ROUTING';
    const TYPE_RECEIVE = 'RECEIVE';

    //Message types
    const MESSAGE_TEXT = 'TEXT';
    const MESSAGE_TEMPLATE = 'TEMPLATE';

    protected $fillable = [
        'uid',
        'task_id',
        'email_server_id',
        'type',
        'last_email',
        'email_from_format',
        'message_subject',
        'message',
        'message_template',
        'message_type',
        'created_at',
        'updated_at',
    ];

    protected $rules = [
        'uid' => 'max:36',
        'task_id' => 'exists:tasks,id',
        'email_server_id' => 'exists:email_servers,id',
        'last_email' => 'required|boolean',
        'email_from_format' => 'required|boolean',
        'type' => 'required|in:' . self::TYPE_AFTER_ROUTING . ',' . self::TYPE_RECEIVE,
        'message_type' => 'required|in:' . self::MESSAGE_TEXT . ',' . self::MESSAGE_TEMPLATE
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
