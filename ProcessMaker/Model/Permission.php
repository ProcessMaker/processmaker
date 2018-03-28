<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 *
 * @property string $PER_UID
 * @property string $PER_CODE
 * @property \Carbon\Carbon $PER_CREATE_DATE
 * @property \Carbon\Carbon $PER_UPDATE_DATE
 * @property int $PER_STATUS
 * @property string $PER_SYSTEM
 */
class Permission extends Model
{

    use Notifiable;

    const PM_FACTORY = 'PM_FACTORY';
    const PM_CASES = 'PM_CASES';
    const PM_SETUP_PROCESS_CATEGORIES = 'PM_SETUP_PROCESS_CATEGORIES';
    const PM_SETUP_PM_TABLES = 'PM_SETUP_PM_TABLES';

    // If the permission is active or not
    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'RBAC_PERMISSIONS';
    protected $primaryKey = 'PER_UID';

    // Do not have primary key be incrementing
    public $incrementing = false;

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'PER_CREATE_DATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'PER_UPDATE_DATE';

    protected $fillable = [
        'PER_UID',
        'PER_CODE',
        'PER_CREATE_DATE',
        'PER_UPDATE_DATE',
        'PER_STATUS',
        'PER_SYSTEM',
    ];
    protected $attributes = [
        'PER_UID'         => '',
        'PER_CODE'        => '',
        'PER_CREATE_DATE' => null,
        'PER_UPDATE_DATE' => null,
        'PER_STATUS'      => '1',
        'PER_SYSTEM'      => '00000000000000000000000000000002',
    ];
    protected $casts = [
        'PER_UID'         => 'string',
        'PER_CODE'        => 'string',
        'PER_STATUS'      => 'int',
        'PER_SYSTEM'      => 'string',
        'PER_CREATE_DATE' => 'datetime',
        'PER_UPDATE_DATE' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'PER_UID';
    }

    /**
     * .
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'RBAC_ROLES_PERMISSIONS',
            'PER_UID',
            'ROL_UID'
        );
    }
}
