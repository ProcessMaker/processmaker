<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

/**
 *
 * @property string $PRO_UID
 * @property string $TAS_UID
 * @property int $TAS_ID
 * @property text $TAS_TITLE
 * @property text $TAS_DESCRIPTION
 * @property string $TAS_TYPE
 * @property float $TAS_DURATION
 */
class Task extends Model
{

    use Notifiable;
    use Uuid;

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'TASK';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = null;

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'process_id',
        'task_id',
        'user_id',
        'TAS_TITLE',
        'TAS_DESCRIPTION',
        'TAS_TYPE',
        'TAS_DURATION',
    ];
    protected $attributes = [
        'TAS_TITLE'                         => '',
        'TAS_DESCRIPTION'                   => '',
        'TAS_TYPE'                          => 'NORMAL',
        'TAS_DURATION'                      => 0,
    ];
    protected $casts = [
        'TAS_TITLE'                         => 'string',
        'TAS_DESCRIPTION'                   => 'string',
        'TAS_TYPE'                          => 'string',
        'TAS_DURATION'                      => 'float',
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
}
