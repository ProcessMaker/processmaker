<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\ProcessCategory;
use Watson\Validating\ValidatingTrait;

/**
 * Represents a business process definition.
 *
 * @property string $PRO_UID
 * @property int $PRO_ID
 * @property string $PRO_NAME
 * @property string $PRO_DESCRIPTION
 * @property string $PRO_PARENT
 * @property float $PRO_TIME
 * @property string $PRO_TIMEUNIT
 * @property string $PRO_STATUS
 * @property string $PRO_TYPE
 * @property bool $PRO_SHOW_MAP
 * @property bool $PRO_SHOW_MESSAGE
 * @property bool $PRO_SUBPROCESS
 * @property string $PRO_TRI_CREATE
 * @property string $PRO_TRI_OPEN
 * @property string $PRO_TRI_DELETED
 * @property string $PRO_TRI_CANCELED
 * @property string $PRO_TRI_PAUSED
 * @property string $PRO_TRI_REASSIGNED
 * @property string $PRO_TRI_UNPAUSED
 * @property string $PRO_VISIBILITY
 * @property bool $PRO_SHOW_DELEGATE
 * @property bool $PRO_SHOW_DYNAFORM
 * @property string $PRO_CATEGORY
 * @property \Carbon\Carbon $PRO_UPDATE_DATE
 * @property \Carbon\Carbon $PRO_CREATE_DATE
 * @property string $PRO_CREATE_USER
 * @property int $PRO_HEIGHT
 * @property int $PRO_WIDTH
 * @property int $PRO_TITLE_X
 * @property int $PRO_TITLE_Y
 * @property int $PRO_DEBUG
 * @property string $PRO_DYNAFORMS
 * @property string $PRO_DERIVATION_SCREEN_TPL
 * @property float $PRO_COST
 * @property string $PRO_UNIT_COST
 * @property int $PRO_ITEE
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

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'PROCESS';
    protected $primaryKey = 'PRO_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'PRO_CREATE_DATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'PRO_UPDATE_DATE';

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
        'PRO_UID',
        'PRO_NAME',
        'PRO_DESCRIPTION',
        'PRO_PARENT',
        'PRO_TIME',
        'PRO_TIMEUNIT',
        'PRO_STATUS',
        'PRO_TYPE',
        'PRO_SHOW_MAP',
        'PRO_SHOW_MESSAGE',
        'PRO_SUBPROCESS',
        'PRO_TRI_CREATE',
        'PRO_TRI_OPEN',
        'PRO_TRI_DELETED',
        'PRO_TRI_CANCELED',
        'PRO_TRI_PAUSED',
        'PRO_TRI_REASSIGNED',
        'PRO_TRI_UNPAUSED',
        'PRO_VISIBILITY',
        'PRO_SHOW_DELEGATE',
        'PRO_SHOW_DYNAFORM',
        'PRO_CATEGORY',
        'PRO_UPDATE_DATE',
        'PRO_CREATE_DATE',
        'PRO_CREATE_USER',
        'PRO_HEIGHT',
        'PRO_WIDTH',
        'PRO_TITLE_X',
        'PRO_TITLE_Y',
        'PRO_DEBUG',
        'PRO_DYNAFORMS',
        'PRO_DERIVATION_SCREEN_TPL',
        'PRO_COST',
        'PRO_UNIT_COST',
        'PRO_ITEE',
        'PRO_ACTION_DONE',
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
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'PRO_UID' => '',
        'PRO_NAME' => '',
        'PRO_DESCRIPTION' => '',
        'PRO_PARENT' => '',
        'PRO_TIME' => 1,
        'PRO_TIMEUNIT' => self::TIMEUNIT_DAYS,
        'PRO_STATUS' => self::STATUS_ACTIVE,
        'PRO_TYPE' => self::TYPE_NORMAL,
        'PRO_SHOW_MAP' => true,
        'PRO_SHOW_MESSAGE' => true,
        'PRO_SUBPROCESS' => false,
        'PRO_TRI_CREATE' => '',
        'PRO_TRI_OPEN' => '',
        'PRO_TRI_DELETED' => '',
        'PRO_TRI_CANCELED' => '',
        'PRO_TRI_PAUSED' => '',
        'PRO_TRI_REASSIGNED' => '',
        'PRO_TRI_UNPAUSED' => '',
        //Change to PRO_DESIGN_ACCESS
        'PRO_VISIBILITY' => self::VISIBILITY_PUBLIC,
        'PRO_SHOW_DELEGATE' => true,
        'PRO_SHOW_DYNAFORM' => false,
        'PRO_CATEGORY' => '',
        'PRO_UPDATE_DATE' => null,
        'PRO_CREATE_DATE' => null,
        'PRO_CREATE_USER' => '',
        'PRO_HEIGHT' => '5000',
        'PRO_WIDTH' => '10000',
        'PRO_TITLE_X' => '0',
        'PRO_TITLE_Y' => '6',
        'PRO_DEBUG' => false,
        'PRO_DYNAFORMS' => null,
        'PRO_DERIVATION_SCREEN_TPL' => '',
        'PRO_COST' => '0.00',
        'PRO_UNIT_COST' => '',
        'PRO_ITEE' => 0,
        'PRO_ACTION_DONE' => null,
        //From BPMN:
        'DIA_UID'           => null,
        'PRO_IS_EXECUTABLE' => false,
        'PRO_IS_CLOSED'     => false,
        'PRO_IS_SUBPROCESS' => false,
        //From Project:
        'PRO_TARGET_NAMESPACE'   => null,
        'PRO_EXPRESSION_LANGUAGE' => null,
        'PRO_TYPE_LANGUAGE'      => null,
        'PRO_EXPORTER'           => null,
        'PRO_EXPORTER_VERSION'   => null,
        'PRO_AUTHOR'             => null,
        'PRO_AUTHOR_VERSION'     => null,
        'PRO_ORIGINAL_SOURCE'    => null
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'PRO_UID' => 'string',
        'PRO_NAME' => 'string',
        'PRO_DESCRIPTION' => 'string',
        'PRO_PARENT' => 'string',
        'PRO_TIME' => 'float',
        'PRO_TIMEUNIT' => 'string',
        'PRO_STATUS' => 'string',
        'PRO_TYPE' => 'string',
        'PRO_SHOW_MAP' => 'boolean',
        'PRO_SHOW_MESSAGE' => 'boolean',
        'PRO_SUBPROCESS' => 'boolean',
        'PRO_TRI_CREATE' => 'string',
        'PRO_TRI_OPEN' => 'string',
        'PRO_TRI_DELETED' => 'string',
        'PRO_TRI_CANCELED' => 'string',
        'PRO_TRI_PAUSED' => 'string',
        'PRO_TRI_REASSIGNED' => 'string',
        'PRO_TRI_UNPAUSED' => 'string',
        'PRO_SHOW_DELEGATE' => 'boolean',
        'PRO_SHOW_DYNAFORM' => 'boolean',
        'PRO_CATEGORY' => 'string',
        'PRO_UPDATE_DATE' => 'datetime',
        'PRO_CREATE_DATE' => 'datetime',
        'PRO_CREATE_USER' => 'string',
        'PRO_HEIGHT' => 'int',
        'PRO_WIDTH' => 'int',
        'PRO_TITLE_X' => 'int',
        'PRO_TITLE_Y' => 'int',
        'PRO_DEBUG' => 'boolean',
        'PRO_DYNAFORMS' => 'string',
        'PRO_DERIVATION_SCREEN_TPL' => 'string',
        'PRO_COST' => 'float',
        'PRO_UNIT_COST' => 'string',
        'PRO_ITEE' => 'int',
        'PRO_ACTION_DONE' => 'string',
        //From BPMN:
        'DIA_UID'           => 'string',
        'PRO_TYPE'          => 'string',
        'PRO_IS_EXECUTABLE' => 'boolean',
        'PRO_IS_CLOSED'     => 'boolean',
        'PRO_IS_SUBPROCESS' => 'boolean',
        //From Project:
        'PRO_TARGET_NAMESPACE' => 'string',
        'PRO_EXPRESSION_LANGUAGE' => 'string',
        'PRO_TYPE_LANGUAGE' => 'string',
        'PRO_EXPORTER' => 'string',
        'PRO_EXPORTER_VERSION' => 'string',
        'PRO_AUTHOR' => 'string',
        'PRO_AUTHOR_VERSION' => 'string',
        'PRO_ORIGINAL_SOURCE' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'PRO_UID' => 'required|max:32',
        'PRO_NAME' => 'required',
        'PRO_PARENT' => 'nullable|max:32',
        'PRO_TIME' => 'required',
        'PRO_TIMEUNIT' => 'required|in:' . self::TIMEUNIT_HOURS . ',' . self::TIMEUNIT_DAYS . ',' . self::TIMEUNIT_MINUTES,
        'PRO_STATUS' => 'required|in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE,
        'PRO_TYPE' => 'required|max:256',
        'PRO_SHOW_MAP' => 'required|boolean',
        'PRO_SHOW_MESSAGE' => 'required|boolean',
        'PRO_SUBPROCESS' => 'required|boolean',
        'PRO_TRI_CREATE' => 'nullable|max:32',
        'PRO_TRI_OPEN' => 'nullable|max:32',
        'PRO_TRI_DELETED' => 'nullable|max:32',
        'PRO_TRI_CANCELED' => 'nullable|max:32',
        'PRO_TRI_PAUSED' => 'nullable|max:32',
        'PRO_TRI_REASSIGNED' => 'nullable|max:32',
        'PRO_TRI_UNPAUSED' => 'nullable|max:32',
        'PRO_VISIBILITY' => 'required|in:' . self::VISIBILITY_PRIVATE . ',' . self::VISIBILITY_PUBLIC,
        'PRO_SHOW_DELEGATE' => 'required|boolean',
        'PRO_SHOW_DYNAFORM' => 'required|boolean',
        'PRO_CATEGORY' => 'nullable|max:32',
        'PRO_CREATE_USER' => 'required|max:32',
        'PRO_HEIGHT' => 'required',
        'PRO_WIDTH' => 'required',
        'PRO_TITLE_X' => 'required',
        'PRO_TITLE_Y' => 'required',
        'PRO_DEBUG' => 'required|boolean',
        'PRO_DERIVATION_SCREEN_TPL' => 'nullable|string|max:128',
        'PRO_UNIT_COST' => 'nullable|string|max:50',
        'PRO_ITEE' => 'required',
        'DIA_UID'           => 'nullable|max:32',
        'PRO_IS_EXECUTABLE' => 'required|boolean',
        'PRO_IS_CLOSED'     => 'required|boolean',
        'PRO_IS_SUBPROCESS' => 'required|boolean'
    ];

    /**
     * Determines if the provided user is a supervisor for this process
     * @param User $user
     * @return boolean
     */
    public function isSupervisor(User $user)
    {
        // First determine if we're a direct supervisor
        if (DB::table('PROCESS_USER')->where('PRO_UID', $this->PRO_UID)
            ->where('USR_UID', $user->USR_UID)
            ->where('PU_TYPE', 'SUPERVISOR')
            ->exists()) {
            return true;
        }

        // If not found, let's determine if we're in any of the supervisor groups
        return DB::table('PROCESS_USER')->where('PRO_UID', $this->PRO_UID)
            ->whereIn('USR_UID', $user->groups()->pluck('GROUPWF.GRP_UID'))
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
                'PRO_UID' => $this->PRO_UID,
                'USR_UID' => $user->USR_UID,
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
        if (DB::table('PROCESS_USER')->where('PRO_UID', $this->PRO_UID)
            ->where('USR_UID', $group->GRP_UID)
            ->where('PU_TYPE', 'GROUP_SUPERVISOR')
            ->exists()) {
            DB::table('PROCESS_USER')->insert([
                'PRO_UID' => $this->PRO_UID,
                'USR_UID' => $group->GRP_UID,
                'PU_TYPE' => 'SUPERVISOR'
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
        return 'PRO_UID';
    }

    /**
     * Tasks owned by this process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(
            Task::class,
            "PRO_UID",
            "PRO_UID"
        );
    }

    /**
     * Collection of DbSources configured in the process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dbSources()
    {
        return $this->hasMany(
            DbSource::class,
            'PRO_UID',
            'PRO_UID'
        );
    }

    /**
     * Category of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(
            ProcessCategory::class,
            "PRO_CATEGORY",
            "CATEGORY_UID"
        );
    }

    /**
     * Cases of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cases()
    {
        return $this->hasMany(Application::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Diagram of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagram()
    {
        return $this->hasOne(Diagram::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Activities of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Events of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Gateways of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gateways()
    {
        return $this->hasMany(Gateway::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Flows of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function flows()
    {
        return $this->hasMany(Flow::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Artifacts of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function artifacts()
    {
        return $this->hasMany(Artifact::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Lanesets of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lanesets()
    {
        return $this->hasMany(Laneset::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Lanes of the process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lanes()
    {
        return $this->hasMany(Lane::class, "PRO_ID", "PRO_ID");
    }

    /**
     * Collection of ProcessVariables configured in the process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(
            ProcessVariable::class,
            'PRO_ID',
            'PRO_ID'
        );
    }

    /**
     * Collection of instances of the process
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instances()
    {
        return $this->hasMany(
            Application::class,
            'PRO_UID',
            'PRO_UID'
        );
    }
}
