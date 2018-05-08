<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Intermediate Email Event.
 * Used to send an email notification when this event is triggered during the execution
 * of a process.
 *
 * @property \ProcessMaker\Model\ProcessFile $processFile
 */
class EmailEvent extends Model
{

    use Notifiable;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'EMAIL_EVENT';
    protected $primaryKey = 'EMAIL_EVENT_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'EMAIL_EVENT_CREATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'EMAIL_EVENT_UPDATE';

    protected $fillable = [
        'EMAIL_EVENT_UID',
        'process_id',
        'EVN_UID',
        'EMAIL_EVENT_FROM',
        'EMAIL_EVENT_TO',
        'EMAIL_EVENT_SUBJECT',
        'PRF_UID',
        'EMAIL_SERVER_UID',
        'EMAIL_EVENT_CREATE',
        'EMAIL_EVENT_UPDATE',
    ];
    protected $attributes = [
        'EMAIL_EVENT_UID'     => null,
        'EVN_UID'             => null,
        'EMAIL_EVENT_FROM'    => '',
        'EMAIL_EVENT_TO'      => null,
        'EMAIL_EVENT_SUBJECT' => '',
        'PRF_UID'             => '',
        'EMAIL_SERVER_UID'    => '',
        'EMAIL_EVENT_CREATE'  => null,
        'EMAIL_EVENT_UPDATE'  => null,
    ];
    protected $casts = [
        'EMAIL_EVENT_UID'     => 'string',
        'EVN_UID'             => 'string',
        'EMAIL_EVENT_FROM'    => 'string',
        'EMAIL_EVENT_TO'      => 'text',
        'EMAIL_EVENT_SUBJECT' => 'string',
        'PRF_UID'             => 'string',
        'EMAIL_SERVER_UID'    => 'string',
        'EMAIL_EVENT_CREATE'  => 'datetime',
        'EMAIL_EVENT_UPDATE'  => 'datetime',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'EMAIL_EVENT_UID';
    }

    /**
     * Owner process file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processFile()
    {
        return $this->belongsTo(
            ProcessFile::class,
            "PRF_UID",
            "PRF_UID"
        );
    }
}
