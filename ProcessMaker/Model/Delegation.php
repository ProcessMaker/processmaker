<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents an Eloquent model of a Group
 * @package ProcessMaker\Model
 */
class Delegation extends Model
{

    // Specify our table and our primary key
    protected $table = 'APP_DELEGATION';
    protected $primaryKey = 'DELEGATION_ID';

    // We do not store timestamps for these tables
    public $timestamps = false;

    /**
     * Returns the relationship of the parent application
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'APP_UID', 'APP_UID');
    }

    /**
     * Returns the relationship of the parent user
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'USR_UID', 'USR_UID');
    }

    /**
     * Returns the relationship of the parent task
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'TAS_UID', 'TAS_UID');
    }
}
