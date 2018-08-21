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

    const PROCESSMAKER_ADMIN = 1;
    const PROCESSMAKER_OPERATOR = 2;
    const PROCESSMAKER_MANAGER = 3;
    const PROCESSMAKER_GUEST = 4;

    // If the role is active or not
    const STATUS_INACTIVE = 'INACTIVE';
    const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at',
        'status',
    ];

    protected $hidden = [
        'id'
    ];
/**
     * Validation rules.
     *
     * @var array $rules
     */
    public static function rules() {
        
        return [
        'name' => 'required|max:255',
        'description' => 'max:255',
        'status' => 'required|in:ACTIVE,INACTIVE'
        ];
    }


    /**
     * The key to use in routes to fetch a user
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Parent role, if provided
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->hasOne(Role::class, 'parent_role_id');
    }

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
