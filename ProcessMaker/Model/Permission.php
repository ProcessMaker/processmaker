<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

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
    use Uuid;

    const PM_FACTORY = 'PM_FACTORY';
    const PM_CASES = 'PM_CASES';
    const PM_SETUP_PROCESS_CATEGORIES = 'PM_SETUP_PROCESS_CATEGORIES';
    const PM_SETUP_PM_TABLES = 'PM_SETUP_PM_TABLES';

    // If the permission is active or not
    const STATUS_DISABLED = 'DISABLED';
    const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'uid',
        'code',
        'created_at',
        'updated_at',
        'status'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * .
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles()
    {
        return $this->belongsToMany( Role::class);
    }
}
