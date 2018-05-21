<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Traits\Uuid;
use ProcessMaker\Model\User;
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
 * @property string $category
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property string $creator_user_id
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
        'category',
        'updated_at',
        'created_at',
        'creator_user_id',
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
        'create_trigger_id' => 'exists:triggers',
        'open_trigger_id' => 'exists:triggers',
        'deleted_trigger_id' => 'nullable|max:32',
        'canceled_trigger_id' => 'nullable|max:32',
        'paused_trigger_id' => 'nullable|max:32',
        'reassigned_trigger_id' => 'nullable|max:32',
        'unpaused_trigger_id' => 'nullable|max:32',
        'category' => 'max:32',
        'creator_user_id' => 'exists:users,id',
    ];

    /**
     * Determines if the provided user is a supervisor for this process
     * @param User $user
     * @return boolean
     */
    public function isSupervisor(User $user)
    {
        // First determine if we're a direct supervisor
        if (DB::table('PROCESS_USER')->where('PRO_UID', $this->uid)
            ->where('USR_UID', $user->uid)
            ->where('PU_TYPE', 'SUPERVISOR')
            ->exists()) {
            return true;
        }

        // If not found, let's determine if we're in any of the supervisor groups
        return DB::table('PROCESS_USER')->where('PRO_UID', $this->id)
            ->whereIn('USR_UID', $user->groups()->pluck('groups.uid'))
            ->where('PU_TYPE', 'GROUP_SUPERVISOR')
            ->exists();
    }

    /**
     * Adds a user as a supervisor for this process
     * @param User $user
     */
    public function addUserSupervisor(User $user)
    {
        if (!$this->isSupervisor($user)) {
            DB::table('PROCESS_USER')->insert([
                'PU_UID'  => \Ramsey\Uuid\Uuid::uuid4(),
                'PRO_UID' => $this->uid,
                'process_id' => $this->id,
                'USR_UID' => $user->uid,
                'PU_TYPE' => 'SUPERVISOR'
            ]);
        }
    }

    /**
     * Add a group as a collection of supervisors for this process
     * @param Group $group
     */
    public function addGroupSupervisor(Group $group)
    {
        if (!DB::table('PROCESS_USER')->where('process_id', $this->id)
            ->where('USR_UID', $group->uid)
            ->where('type', 'GROUP_SUPERVISOR')
            ->exists()) {
            DB::table('process_users')->insert([
                'PR_UID' => $this->uid,
                'USR_UID' => $group->uid,
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo( ProcessCategory::class, "id");
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
        return $this->hasMany( ProcessVariable::class, 'PRO_ID', 'id');
    }

    /**
     * Get the creator/author of this process.
     *
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

}
