<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\SqlsrvSupportTrait;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property int $id
 * @property int $process_request_id
 * @property int $process_request_token_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property ProcessRequest $processRequest
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\ProcessMaker\Models\ProcessRequestLock whereNotDue()
 */
class ProcessRequestLock extends Model
{
    use SqlsrvSupportTrait;

    protected $connection = 'data';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'request_ids' => 'array',
    ];

    /**
     * Active block that did not overcome.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNotDue($query)
    {
        return $query->where(function ($query) {
            return $query->whereNull('due_at')
                ->orWhere('due_at', '>', now());
        });
    }

    /**
     * @return void
     */
    public function activate()
    {
        $numSeconds = config('app.bpmn_actions_max_lock_time', 1) ?: 60;
        $this->due_at = now()->modify(now()->modify("+{$numSeconds} seconds"));
        $this->save();
    }
}
