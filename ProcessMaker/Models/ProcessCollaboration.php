<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents an Eloquent model of a Request which is an instance of a Process.
 *
 * @property string $id
 * @property string $process_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessCollaboration extends Model
{

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
    protected $ids = [
        'process_id',
    ];

    /**
     * Get requests in the collaboration.
     *
     */
    public function requests()
    {
        return $this->hasMany(ProcessRequest::class);
    }
}
