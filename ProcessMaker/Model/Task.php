<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

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
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'TASK';
    protected $primaryKey = 'TAS_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = null;

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'PRO_UID',
        'TAS_UID',
        'TAS_ID',
        'TAS_TITLE',
        'TAS_DESCRIPTION',
        'TAS_TYPE',
        'TAS_DURATION',
    ];
    protected $attributes = [
        'PRO_UID'                           => '',
        'TAS_UID'                           => '',
        'TAS_ID'                            => null,
        'TAS_TITLE'                         => '',
        'TAS_DESCRIPTION'                   => '',
        'TAS_TYPE'                          => 'NORMAL',
        'TAS_DURATION'                      => 0,
    ];
    protected $casts = [
        'PRO_UID'                           => 'string',
        'TAS_UID'                           => 'string',
        'TAS_ID'                            => 'int',
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
        return 'TAS_UID';
    }
}
