<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents an Eloquent model of a Group
 * @package ProcessMaker\Model
 */
class Group extends Model
{

    // Specify our table and our primary key
    protected $table = 'GROUPWF';
    protected $primaryKey = 'GRP_UID';

    // We do not store timestamps for these tables
    public $timestamps = false;

    /**
     * Returns the relationship of users that belong to this group
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'GROUP_USER', 'GRP_UID', 'USR_UID');
    }

    /**
     * Get all groups assigned to task
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function assignedTask()
    {
        return $this->morphMany(TaskUser::class, 'assigned', 'assigned_type', 'GRP_ID', 'USR_ID');
    }
}
