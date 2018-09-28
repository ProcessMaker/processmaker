<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Spatie\BinaryUuid\HasBinaryUuid;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * Represents a business process definition.
 *
 * @property string $uuid
 * @property string $uuid_text
 * @property string $process_category_uuid
 * @property string $process_category_uuid_text
 * @property string $user_uuid
 * @property string $user_uuid_text
 * @property string $bpmn
 * @property string $description
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class Process extends Model implements HasMedia
{

    use HasBinaryUuid;
    use HasMediaTrait;

    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'uuid',
        'user_uuid',
        'bpmn',
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
        'bpmn'
    ];

    /**
     * Parsed process BPMN definitions.
     *
     * @var \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    private $bpmnDefinitions;

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_category_uuid',
        'user_uuid',
    ];

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_uuid');
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
            'name' => 'required|unique:processes,name',
            'description' => 'required',
            'status' => 'in:ACTIVE,INACTIVE',
            'process_category_uuid' => 'nullable|exists:process_categories,uuid',
            //'user_uuid' => 'exists:users,uuid',
        ];

        if ($existing) {
            // ignore the unique rule for this id
            $rules['name'] = [
                'required',
                Rule::unique('processes')->ignore($existing->uuid, 'uuid')
            ];
        }

        return $rules;
    }

    /**
     * Get the creator/author of this process.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }

    /**
     * Get the process definitions from BPMN field.
     *
     * @return ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    public function getDefinitions()
    {
        if (empty($this->bpmnDefinitions)) {
            $this->bpmnDefinitions = app(BpmnDocumentInterface::class, ['process' => $this]);
            if ($this->bpmn) {
                $this->bpmnDefinitions->loadXML($this->bpmn);
                //Load the collaborations if exists
                $collaborations = $this->bpmnDefinitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'collaboration');
                foreach($collaborations as $collaboration) {
                    $collaboration->getBpmnElementInstance();
                }
            }
        }
        return $this->bpmnDefinitions;
    }

    /**
     * Get the path of the process templates.
     *
     * @return string
     */
    public static function getProcessTemplatesPath()
    {
        return Storage::disk('process_templates')->path('');
    }

    /**
     * Get a process template by name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function getProcessTemplate($name)
    {
        return Storage::disk('process_templates')->get($name);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function requests()
    {
        return $this->hasMany(ProcessRequest::class);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function collaborations()
    {
        return $this->hasMany(ProcessCollaboration::class);
    }

    /**
     * Get the user to whom to assign a task.
     *
     * @param ActivityInterface $activity
     *
     * @return User
     */
    public function getNextUser(ActivityInterface $activity)
    {
        $default = $activity instanceof ScriptTaskInterface ? 'script' : 'cyclical';
        $assignmentType = $activity->getProperty('assignment_type', $default);
        switch ($assignmentType) {
            case 'cyclical':
                $user = $this->getNextUserCyclicalAssignment($activity->getId());
                break;
            case 'manual':
            case 'self_service':
                $user = null;
                break;
            case 'script':
            default:
                $user = null;
        }
        return $user ? User::withUuid($user)->first() : null;
    }

    /**
     * Get the next user in a cyclical assignment.
     *
     * @param string $processTaskUuid
     * 
     * @return binary
     * @throws TaskDoesNotHaveUsersException
     */
    private function getNextUserCyclicalAssignment($processTaskUuid)
    {
        $last = ProcessRequestToken::where('process_uuid', $this->uuid)
            ->where('element_uuid', $processTaskUuid)
            ->orderBy('created_at', 'desc')
            ->first();
        $users = $this->getAssignableUsers($processTaskUuid);
        if (empty($users)) {
            throw new TaskDoesNotHaveUsersException($processTaskUuid);
        }
        sort($users);
        if ($last) {
            foreach ($users as $user) {
                if ($user > $last->user_uuid) {
                    return $user;
                }
            }
        }
        return $users[0];
    }

    /**
     * Get an array of all assignable users to a task.
     * 
     * @param string $processTaskUuid
     * 
     * @return array
     */
    public function getAssignableUsers($processTaskUuid)
    {
        $assignments = ProcessTaskAssignment::select(['assignment_uuid', 'assignment_type'])
                ->where('process_uuid', $this->uuid)
                ->where('process_task_uuid', $processTaskUuid)
                ->get();
        $users = [];
        foreach ($assignments as $assignment) {
            if ($assignment->assignment_type === 'user') {
                $users[$assignment->assignment_uuid] = $assignment->assignment_uuid;
            } else {
                $this->getConsolidatedUsers($assignment->assignment_uuid, $users);
            }
        }
        return array_values($users);
    }

    /**
     * Get a consolidated list of users within groups.
     *
     * @param binary $group_uuid
     * @param array $users
     * 
     * @return array
     */
    private function getConsolidatedUsers($group_uuid, array &$users)
    {
        $groupMembers = GroupMember::where('group_uuid', $group_uuid)->get();
        foreach ($groupMembers as $groupMember) {
            if ($groupMember->member_type === 'user') {
                $users[$groupMember->member_uuid] = $groupMember->member_uuid;
            } else {
                $this->getConsolidatedUsers($groupMember->member_uuid, $users);
            }
        }
        return $users;
    }

    /**
     * Get a list of the process start events.
     *
     * @return array
     */
    public function getStartEvents()
    {
        $definitions = $this->getDefinitions();
        $response = [];
        $startEvents = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'startEvent');
        foreach ($startEvents as $startEvent) {
            $response[] = $startEvent->getBpmnElementInstance()->getProperties();
        }
        return $response;
    }

    /**
     * Process events relationship.
     *
     * @return \ProcessMaker\Models\ProcessEvents
     */
    public function events()
    {
        $query = $this->newQuery();
        $query->where('uuid', $this->uuid);
        return new ProcessEvents($query, $this);
    }
}
