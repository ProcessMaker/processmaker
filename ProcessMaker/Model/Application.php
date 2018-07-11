<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Model\Traits\Uuid;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
use Ramsey\Uuid\Uuid as UuidGenerator;

/**
 * Represents an Eloquent model of an Case which is an instance of a Process. It's called Application because of the
 * case keyword in php being unavailable for use
 * @package ProcessMaker\Model
 */
class Application extends Model implements ExecutionInstanceInterface
{
    use Uuid, ExecutionInstanceTrait;

    // Specify our table and our primary key
    protected $table = 'APPLICATION';

    // Set our updated/created at
    const UPDATED_AT = 'APP_UPDATE_DATE';
    const CREATED_AT = 'APP_CREATE_DATE';

    // Our application status enums
    /**
     * @todo Is PAUSED the right value?
     */
    const STATUS_PAUSED = 0;
    const STATUS_DRAFT = 1;
    const STATUS_TO_DO = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'APP_INIT_DATE',
        'APP_FINISH_DATE',
    ];

    /**
     * Hidden properties
     */
    protected $hidden = [
        'id',
        'creator_user_id',
        'process_id',
        'APP_DATA'
    ];

    /**
     * Boot application as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument=[])
    {
        parent::__construct($argument);
        $this->bootElement([]);
        $this->setId(UuidGenerator::uuid4());
    }

    /*
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Returns relationship of the User who created this case
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id', 'id');
    }

    /**
     * Returns the relationship of the User this case belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'APP_CUR_USER', 'USR_UID');
    }

    /**
     * Returns the relationship of the Process this case belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id', 'id');
    }

    /**
     * Returns the relationship of the Delegation this case belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function delegations()
    {
        return $this->hasMany(Delegation::class, 'application_id', 'id');
    }

    /**
     * Returns the relationship of the Delegation this case belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function threads()
    {
        return $this->hasMany(Thread::class, 'APP_UID', 'APP_UID');
    }

    /**
     * Returns the unserialized data model for this application
     * @return mixed
     */
    public function getData()
    {
        return json_decode($this->APP_DATA);
    }

    /**
     * Determines if a user has participated in this application.  This is done by checking if any delegations
     * match this application and passed in user.
     * @param User $user User to check
     * @return boolean True if the user participated in this Case in some way
     */
    public function hasUserParticipated(User $user)
    {
        // Check if there is a delegation that was / is assigned for this
        return DB::table('delegations')
            ->where('application_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
   }
}
