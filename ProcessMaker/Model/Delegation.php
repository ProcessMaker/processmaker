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
    protected $primaryKey = 'DEL_INDEX';

    // We do not store timestamps for these tables
    public $timestamps = false;

    /**
     * Returns the relationship of application that belong to this group
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function application()
    {
        return $this->belongsToMany(Application::class, 'APP_UID', 'APP_UID');
    }
}
