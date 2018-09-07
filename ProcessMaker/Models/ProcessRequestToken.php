<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * ProcessRequestToken is used to store the state of a token of the
 * Nayra engine
 *
 * @property string uuid
 * @property \Carbon\Carbon completed_at
 * @property \Carbon\Carbon due_at
 * @property string element_uuid
 * @property string element_type
 * @property \Carbon\Carbon initiated_at
 * @property string process_request_uuid
 * @property \Carbon\Carbon riskchanges_at
 * @property string status
 * @property string user_uuid
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessRequestToken extends Model
{
    use HasBinaryUuid;

    /**
     * Statuses:
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_FAILING = 'FAILING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_EVENT_CATCH = 'EVENT_CATCH';

    public $incrementing = false;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
     */
    protected $guarded = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'bpmn'
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_request_uuid'
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function getRules()
    {
        return [
            'element_uuid' => 'required',
            'element_type' => 'required',
            'status' => 'required|in:' . self::STATUS_ACTIVE
                                . ',' . self::STATUS_FAILING
                                . ', '. self::STATUS_COMPLETED
                                . ', '. self::STATUS_FAILING
                                . ', '. self::STATUS_EVENT_CATCH,
            'process_request_uuid' => 'required|exists:process_requests,uuid',
        ];
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'process_request_uuid');
    }
}