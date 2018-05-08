<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Permisson;
use ProcessMaker\Model\Traits\Uuid;

/**
 * A role is a collection of permissions defined for the system and can
 * be assigned to the users. A role can be identified by a code,
 * there are four basic roles: PROCESSMAKER_ADMIN, PROCESSMAKER_OPERATOR,
 * PROCESSMAKER_MANAGER and PROCESSMAKER_GUEST.
 *
 * @package ProcessMaker\Model
 *
 * @property \Illuminate\Database\Eloquent\Collection $permissions
 */
class Role extends Model
{

    use Notifiable;
    use Uuid;

    const PROCESSMAKER_ADMIN = 'PROCESSMAKER_ADMIN';
    const PROCESSMAKER_OPERATOR = 'PROCESSMAKER_OPERATOR';
    const PROCESSMAKER_MANAGER = 'PROCESSMAKER_MANAGER';
    const PROCESSMAKER_GUEST = 'PROCESSMAKER_GUEST';

    // If the role is active or not
    const STATUS_DISABLED = 'DISABLED';
    const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'parent_role_id',
        'code',
        'created_at',
        'updated_at',
        'status',
    ];

    /**
     * Users of the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Returns permissions belonging to this role 
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany( Permission::class);
    }
}
