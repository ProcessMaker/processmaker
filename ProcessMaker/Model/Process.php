<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Represents a business process definition.
 *
 * @package ProcessMaker\Model
 */
class Process extends Model
{
    // Set our table name
    protected $table = 'PROCESS';
    protected $primaryKey = 'PRO_ID';
    // We do have a created at, but we don't store an updated at
    const CREATED_AT = 'PRO_CREATE_DATE';
    const UPDATED_AT = null;

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
