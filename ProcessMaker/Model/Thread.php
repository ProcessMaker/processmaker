<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents an Eloquent model of a Group
 * @package ProcessMaker\Model
 */
class Thread extends Model
{

    // Specify our table and our primary key
    protected $table = 'APP_THREAD';
    protected $primaryKey = 'APP_THREAD_INDEX';

    // We do not store timestamps for these tables
    public $timestamps = false;

    /**
     * Returns the relationship of applications that the thread belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function application()
    {
        return $this->belongsToMany(Application::class, 'APP_UID', 'APP_UID');
    }

    /**
     * Returns the relationship of delegation that the thread belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function delegation()
    {
        return $this->belongsToMany(Delegation::class, 'DEL_INDEX', 'DEL_INDEX');
    }
}
