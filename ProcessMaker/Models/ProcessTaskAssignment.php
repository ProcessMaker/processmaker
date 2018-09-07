<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents a business process task assignment definition.
 *
 * @property string $uuid
 * @property string process_task_uuid
 * @property string assignment_uuid
 * @property string assignment_type
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessTaskAssignment extends Model
{
    use HasBinaryUuid;

    //values for assignment_type
    const TYPE_USER = 'USER';
    const TYPE_GROUP = 'GROUP';

    protected $fillable = [
        'process_task_uuid',
        'assignment_uuid',
        'assignment_type'
    ];

    public static function rules($existing = null)
    {
        return [
            'process_task_uuid' => 'required|exists:processes,uuid',
            'assignment_uuid' => 'required',
            'assignment_type' => 'required|in:' . self::TYPE_USER . ',' . self::TYPE_GROUP,
        ];
    }

}
