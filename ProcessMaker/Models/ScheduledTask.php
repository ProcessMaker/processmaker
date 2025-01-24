<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    public static function rules()
    {
        return [
            'process_id' => 'required',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return BelongsTo
     */
    public function processRequest(): BelongsTo
    {
        return $this->belongsTo(ProcessRequest::class);
    }

    /**
     * @return BelongsTo
     */
    public function processRequestToken(): BelongsTo
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
