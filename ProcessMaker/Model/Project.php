<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Project groups the elements of a BPMN diagram of a process.
 *
 * @package ProcessMaker\Model
 *
 * @property \ProcessMaker\Model\Process $process
 */
class Project extends Model
{

    use Notifiable;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_PROJECT';
    protected $primaryKey = 'PRJ_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'PRJ_CREATE_DATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'PRJ_UPDATE_DATE';

    protected $fillable = [
        'PRJ_UID',
        'PRJ_NAME',
        'PRJ_DESCRIPTION',
        'PRJ_TARGET_NAMESPACE',
        'PRJ_EXPRESION_LANGUAGE',
        'PRJ_TYPE_LANGUAGE',
        'PRJ_EXPORTER',
        'PRJ_EXPORTER_VERSION',
        'PRJ_CREATE_DATE',
        'PRJ_UPDATE_DATE',
        'PRJ_AUTHOR',
        'PRJ_AUTHOR_VERSION',
        'PRJ_ORIGINAL_SOURCE',
    ];
    protected $attributes = [
        'PRJ_UID'                => '',
        'PRJ_NAME'               => '',
        'PRJ_DESCRIPTION'        => null,
        'PRJ_TARGET_NAMESPACE'   => null,
        'PRJ_EXPRESION_LANGUAGE' => null,
        'PRJ_TYPE_LANGUAGE'      => null,
        'PRJ_EXPORTER'           => null,
        'PRJ_EXPORTER_VERSION'   => null,
        'PRJ_CREATE_DATE'        => null,
        'PRJ_UPDATE_DATE'        => null,
        'PRJ_AUTHOR'             => null,
        'PRJ_AUTHOR_VERSION'     => null,
        'PRJ_ORIGINAL_SOURCE'    => null,
    ];
    protected $casts = [
        'PRJ_UID'                => 'string',
        'PRJ_NAME'               => 'string',
        'PRJ_DESCRIPTION'        => 'string',
        'PRJ_TARGET_NAMESPACE'   => 'text',
        'PRJ_EXPRESION_LANGUAGE' => 'text',
        'PRJ_TYPE_LANGUAGE'      => 'text',
        'PRJ_EXPORTER'           => 'text',
        'PRJ_EXPORTER_VERSION'   => 'text',
        'PRJ_CREATE_DATE'        => 'datetime',
        'PRJ_UPDATE_DATE'        => 'datetime',
        'PRJ_AUTHOR'             => 'text',
        'PRJ_AUTHOR_VERSION'     => 'text',
        'PRJ_ORIGINAL_SOURCE'    => 'text',
    ];
    protected $events = [
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'PRJ_UID';
    }

    /**
     * Process related to this project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class, "PRJ_UID", "PRO_UID");
    }
}
