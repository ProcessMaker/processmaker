<?php

namespace ProcessMaker\Models;

use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a task that will be scheduled to run
 */
class ScheduledTask extends ProcessMakerModel
{
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    protected $fillable = [
        'process_id', 'process_request_id', 'process_request_token_id', 'configuration',
        'type', 'last_execution', 'claimed_by', 'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public static function rules()
    {
        return [
            'process_id' => 'required',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processRequestToken()
    {
        return $this->belongsTo(ProcessRequestToken::class);
    }

    public function fillStartEvents()
    {
        $processes = Process::all();
        foreach ($processes as $process) {
        }
    }
}
