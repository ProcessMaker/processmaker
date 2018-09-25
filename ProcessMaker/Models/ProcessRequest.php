<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
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
 * @property Process $process
 *
 */
class ProcessRequest extends Model implements ExecutionInstanceInterface
{

    use ExecutionInstanceTrait;
    use HasBinaryUuid;

    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'uuid',
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'initiated_at' => 'datetime',
        'data' => 'array'
    ];

    /**
     * Boot the model as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument=[])
    {
        parent::__construct($argument);
        $this->bootElement([]);
    }

    /**
     * Validation rules.
     *
     * @param null $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $rules = [
            'name' => 'required|unique:process_requests,name',
            'data' => 'required',
            'status' => 'in:DRAFT,ACTIVE,COMPLETED',
            'process_uuid' => 'required|exists:processes,uuid',
            'process_collaboration_uuid' => 'nullable|exists:process_collaborations,uuid',
            'user_uuid' => 'exists:users,uuid',
        ];

        if ($existing) {
            // ignore the unique rule for this id
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                Rule::unique('process_requests')->ignore($existing->uuid, 'uuid')
            ];
        }

        return $rules;
    }

    /**
     * Determines if a user has participated in this application.  This is done by checking if any delegations
     * match this application and passed in user.
     *
     * @param User $user User to check
     *
     * @return boolean True if the user participated in this Case in some way
     */
    public function hasUserParticipated(User $user)
    {
        return $this->tokens()
            ->where('user_uuid', $user->uuid)
            ->exists();
    }

    /**
     * Get tokens of the request.
     *
     */
    public function tokens()
    {
        return $this->hasMany(ProcessRequestToken::class);
    }

    /**
     * Get the creator/author of this request.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }

    /**
     * Get collaboration of this request.
     *
     */
    public function collaboration()
    {
        return $this->belongsTo(ProcessCollaboration::class, 'process_collaboration_uuid');
    }

    /**
     * Get the creator/author of this request.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
