<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property string $uuid
 * @property string $uuid_text
 * @property string $process_uuid
 * @property string $process_uuid_text
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessCollaboration extends Model
{

    use HasBinaryUuid;

    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_uuid',
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function getRules()
    {
        return [
            'process_uuid' => 'required|exists:processes,uuid',
        ];
    }

    /**
     * Get requests in the collaboration.
     *
     */
    public function requests()
    {
        return $this->hasMany(ProcessRequest::class);
    }
}
