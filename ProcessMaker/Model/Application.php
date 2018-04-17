<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Represents an Eloquent model of an Case which is an instance of a Process. It's called Application because of the
 * case keyword in php being unavailable for use
 * @package ProcessMaker\Model
 */
class Application extends Model
{

    // Specify our table and our primary key
    protected $table = 'APPLICATION';
    protected $primaryKey = 'APP_UID';

    // Our primary key is not incrementing
    public $incrementing = false;

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
     * Returns relationship of the User who created this case
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'APP_INIT_USER', 'USR_UID');
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
        return $this->belongsTo(Process::class, 'PRO_UID', 'PRO_UID');
    }

    /**
     * Returns the relationship of the Delegation this case belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function delegation()
    {
        return $this->hasMany(Delegation::class, 'APP_UID', 'APP_UID');
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
        return DB::table('LIST_PARTICIPATED_LAST')->where('APP_UID', $this->APP_UID)
            ->where('USR_UID', $user->USR_UID)
            ->exists();
    }
}
