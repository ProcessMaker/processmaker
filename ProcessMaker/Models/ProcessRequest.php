<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property string $uuid
 * @property string $uuid_text
 * @property string $process_uuid
 * @property string $process_uuid_text
 * @property string $user_uuid
 * @property string $user_uuid_text
 * @property string $process_collaboration_uuid
 * @property string $process_collaboration_uuid_text
 * @property string $participant_uuid
 * @property string $name
 * @property string $status
 * @property string $data
 * @property \Carbon\Carbon $initiated_at
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessRequest extends Model
{

    use HasBinaryUuid;

    /**
     * Statuses:
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_COMPLETED = 'COMPLETED';

    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'uuid',
        'data',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'data'
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_uuid',
        'process_collaboration_uuid',
        'user_uuid',
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function getRules()
    {
        return [
            'name' => 'required',
            'data' => 'required',
            'status' => 'in:' . self::STATUS_ACTIVE . ',' . self::STATUS_COMPLETED,
            'process_uuid' => 'required|exists:processes,uuid',
            'process_collaboration_uuid' => 'nullable|exists:process_collaborations,uuid',
            'user_uuid' => 'exists:users,uuid',
        ];
    }

    /**
     * Get the creator/author of this request.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }
}
