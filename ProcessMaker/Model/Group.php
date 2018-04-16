<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Represents an Eloquent model of a Group
 * @package ProcessMaker\Model
 *
 * @property string GRP_UID
 * @property string GRP_TITLE
 * @property string GRP_STATUS
 * @property string GRP_LDAP_DN
 * @property string GRP_UX
 */
class Group extends Model
{

    // Specify our table and our primary key
    protected $table = 'GROUPWF';
    protected $primaryKey = 'GRP_ID';

    const TYPE = 'GROUP';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    // We do not store timestamps for these tables
    public $timestamps = false;

    protected $fillable = [
        'GRP_UID',
        'GRP_TITLE',
        'GRP_STATUS',
        'GRP_LDAP_DN',
        'GRP_UX'
    ];

    protected $attributes = [
        'GRP_UID' => null,
        'GRP_TITLE' => '',
        'GRP_STATUS' => '',
        'GRP_LDAP_DN' => '',
        'GRP_UX' => 'NORMAL'
    ];
    protected $casts = [
        'GRP_UID' => 'string',
        'GRP_TITLE' => 'string',
        'GRP_STATUS' => 'string',
        'GRP_LDAP_DN' => 'string',
        'GRP_UX' => 'string'
    ];

    protected $rules = [
        'GRP_UID' => 'required|max:32',
        'GRP_TITLE' => 'required',
        'GRP_LDAP_DN' => 'required',
        'GRP_UX' => 'required',
        'GRP_STATUS' => 'required|max:8|in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE
    ];

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
     * @return MorphMany
     */
    public function assignedTask(): MorphMany
    {
        return $this->morphMany(TaskUser::class, 'assignee', 'TU_RELATION', 'USR_ID');
    }
}
