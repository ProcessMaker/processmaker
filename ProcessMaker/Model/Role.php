<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Permisson;

/**
 * A role is a collection of permissions defined for the system and can
 * be assigned to the users. A role can be identified by a code (ROL_CODE),
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
    protected $table = 'RBAC_ROLES';
    protected $primaryKey = 'ROL_UID';

    // Do not have primary key be incrementing
    public $incrementing = false;

    const DEFAULT_SYSTEM = '00000000000000000000000000000002';
    const PROCESSMAKER_ADMIN = 'PROCESSMAKER_ADMIN';
    const PROCESSMAKER_OPERATOR = 'PROCESSMAKER_OPERATOR';
    const PROCESSMAKER_MANAGER = 'PROCESSMAKER_MANAGER';
    const PROCESSMAKER_GUEST = 'PROCESSMAKER_GUEST';

    // If the role is active or not
    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'ROL_CREATE_DATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'ROL_UPDATE_DATE';

    protected $fillable = [
        'ROL_PARENT',
        'ROL_SYSTEM',
        'ROL_CODE',
        'ROL_CREATE_DATE',
        'ROL_UPDATE_DATE',
        'ROL_STATUS',
    ];
    protected $attributes = [
        'ROL_PARENT' => '',
        'ROL_SYSTEM' => self::DEFAULT_SYSTEM,
        'ROL_CODE' => null,
        'ROL_CREATE_DATE' => null,
        'ROL_UPDATE_DATE' => null,
        'ROL_STATUS' => 1,
    ];
    protected $casts = [
        'ROL_UID'         => 'string',
        'ROL_PARENT'      => 'string',
        'ROL_SYSTEM'      => 'string',
        'ROL_CODE'        => 'string',
        'ROL_CREATE_DATE' => 'datetime',
        'ROL_UPDATE_DATE' => 'datetime',
        'ROL_STATUS'      => 'integer',
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
        return $this->belongsToMany(
            Permission::class,
            'RBAC_ROLES_PERMISSIONS',
            "ROL_UID",
            "PER_UID"
        );
    }
}
