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
    ];

    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class);
    }
}
