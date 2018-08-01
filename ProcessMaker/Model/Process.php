<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\Traits\Uuid;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use Watson\Validating\ValidatingTrait;

/**
 * Represents a business process definition.
 *
 * @property string $uid
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $parent_process_id
 * @property float $time
 * @property string $timeunit
 * @property string $status
 * @property string $type
 * @property bool $show_map
 * @property bool $show_message
 * @property string $create_trigger_id
 * @property string $open_trigger_id
 * @property string $deleted_trigger_id
 * @property string $canceled_trigger_id
 * @property string $paused_trigger_id
 * @property string $reassigned_trigger_id
 * @property string $unpaused_trigger_id
 * @property string $visibility_id
 * @property bool $show_delegate
 * @property bool $show_dynaform
 * @property integer process_category_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property int $user_id
 * @property int $height
 * @property int $width
 * @property int $title_x
 * @property int $title_y
 * @property int $debug
 * @property string $dynaforms
 * @property string $derivation_screen_template
 * @property float $cost
 * @property string $unit_cost
 * @property int $itee
 * @property string $PRO_ACTION_DONE
 * @property string $DIA_UID
 * @property bool $PRO_IS_EXECUTABLE
 * @property bool $PRO_IS_CLOSED
 * @property bool $PRO_IS_SUBPROCESS
 * @property string $PRO_TARGET_NAMESPACE
 * @property string $PRO_EXPRESSION_LANGUAGE
 * @property string $PRO_TYPE_LANGUAGE
 * @property string $PRO_EXPORTER
 * @property string $PRO_EXPORTER_VERSION
 * @property string $PRO_AUTHOR
 * @property string $PRO_AUTHOR_VERSION
 * @property string $PRO_ORIGINAL_SOURCE
 * @property \Illuminate\Database\Eloquent\Collection $cases
 * @property string $bpmn
 *
 * @package ProcessMaker\Model
 */
class Process extends Model
{
    use ValidatingTrait;
    use Uuid;

    /**
     * Statuses:
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Time units:
     */
    const TIMEUNIT_HOURS = 'HOURS';
    const TIMEUNIT_DAYS = 'DAYS';
    const TIMEUNIT_MINUTES = 'MINUTES';

    /**
     * ProcessMaker process types.
     */
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_SIMPLIFIED = 'SIMPLIFIED_DESIGNER';

    /**
     * Process Design Access.
     */
    const VISIBILITY_PUBLIC = 'PUBLIC';
    const VISIBILITY_PRIVATE = 'PRIVATE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'uid',
        'name',
        'description',
        'parent',
        'time',
        'timeunit',
        'status',
        'type',
        'show_map',
        'show_message',
        'create_trigger_id',
        'open_trigger_id',
        'deleted_trigger_id',
        'canceled_trigger_id',
        'paused_trigger_id',
        'reassigned_trigger_id',
        'unpaused_trigger_id',
        'visibility',
        'show_delegate',
        'show_dynaform',
        'process_category_id',
        'updated_at',
        'created_at',
        'user_id',
        'height',
        'width',
        'title_x',
        'title_y',
        'debug',
        'dynaforms',
        'derivation_screen_template',
        'cost',
        'unit_cost',
        'itee',
        'action_done',
        //From BPMN:
        'DIA_UID',
        'PRO_IS_EXECUTABLE',
        'PRO_IS_CLOSED',
        'PRO_IS_SUBPROCESS',
        //From Project:
        'PRO_TARGET_NAMESPACE',
        'PRO_EXPRESSION_LANGUAGE',
        'PRO_TYPE_LANGUAGE',
        'PRO_EXPORTER',
        'PRO_EXPORTER_VERSION',
        'PRO_AUTHOR',
        'PRO_AUTHOR_VERSION',
        'PRO_ORIGINAL_SOURCE',
        'PRO_BPMN_TYPE',
    ];

    // Hidden fields when presenting to api or other toArray calls
    // BPMN data will be hidden. It will be able to be retrieved in /processes/{process_id}/bpmn GET call
    protected $hidden = [
        'id',
        'bpmn'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'uid' => 'max:36',
        'name' => 'required',
        'process_parent_id' => 'exists:processes',
        'status' => 'in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE,
        'create_trigger_id' => 'nullable|exists:triggers,id',
        'open_trigger_id' => 'nullable|exists:triggers,id',
        'deleted_trigger_id' => 'nullable|max:32',
        'canceled_trigger_id' => 'nullable|max:32',
        'paused_trigger_id' => 'nullable|max:32',
        'reassigned_trigger_id' => 'nullable|max:32',
        'unpaused_trigger_id' => 'nullable|max:32',
        'process_category_id' => 'exists:process_categories,id',
        'user_id' => 'exists:users,id',
    ];
    
    private $bpmnDefinitions;

    /**
     * Determines if the provided user is a supervisor for this process
     * @param User $user
     * @return boolean
     */
    public function isSupervisor(User $user)
    {
        // First determine if we're a direct supervisor
        if (DB::table('process_users')->where('process_id', $this->id)
            ->where('user_id', $user->id)
            ->where('type', 'SUPERVISOR')
            ->exists()) {
            return true;
        }

        // If not found, let's determine if we're in any of the supervisor groups
        return DB::table('process_users')->where('process_id', $this->id)
            ->whereIn('user_id', $user->groups()->pluck('groups.id'))
            ->where('type', 'GROUP_SUPERVISOR')
            ->exists();
    }

    /**
     * Adds a user as a supervisor for this process
     * @param User $user
     */
    public function addUserSupervisor(User $user)
    {
        if (!$this->isSupervisor($user)) {
            DB::table('process_users')->insert([
                'uid' => \Ramsey\Uuid\Uuid::uuid4(),
                'process_id' => $this->id,
                'user_id' => $user->id,
                'type' => 'SUPERVISOR'
            ]);
        }
    }

    /**
     * Add a group as a collection of supervisors for this process
     * @param Group $group
     */
    public function addGroupSupervisor(Group $group)
    {
        if (!DB::table('process_users')->where('process_id', $this->id)
            ->where('user_id', $group->id)
            ->where('type', 'GROUP_SUPERVISOR')
            ->exists()) {
            DB::table('process_users')->insert([
                'uid' => \Ramsey\Uuid\Uuid::uuid4(),
                'process_id' => $this->id,
                'user_id' => $group->id,
                'type' => 'SUPERVISOR'
            ]);
        }
    }

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
     * Tasks owned by this process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Collection of DbSources configured in the process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dbSources()
    {
        return $this->hasMany(DbSource::class);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_id');
    }

    /**
     * User of the process.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cases of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cases()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Diagram of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagram()
    {
        return $this->hasOne(Diagram::class);
    }

    /**
     * Activities of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Events of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Gateways of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gateways()
    {
        return $this->hasMany(Gateway::class);
    }

    /**
     * Flows of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function flows()
    {
        return $this->hasMany(Flow::class);
    }

    /**
     * Artifacts of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function artifacts()
    {
        return $this->hasMany(Artifact::class);
    }

    /**
     * Lanesets of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lanesets()
    {
        return $this->hasMany(Laneset::class);
    }

    /**
     * Lanes of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lanes()
    {
        return $this->hasMany(Lane::class);
    }

    /**
     * Collection of ProcessVariables configured in the process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ProcessVariable::class, 'process_id', 'id');
    }

    /**
     * Get the creator/author of this process.
     *
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the forms of the process.
     *
     */
    public function forms()
    {
        return $this->hasMany(Form::class);
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
            }
        }
        return $this->bpmnDefinitions;
    }
}
