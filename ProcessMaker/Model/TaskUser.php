<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Task User
 *
 * @package ProcessMaker\Model
 *
 * @property int TAS_USER_ID
 * @property string TAS_UID
 * @property string USR_UID
 * @property int TU_TYPE
 * @property int TU_RELATION
 */
class TaskUser extends Model
{
    use ValidatingTrait;

    protected $table = 'TASK_USER';
    protected $primaryKey = 'TASK_USER_ID';

    public $timestamps = false;

    protected $fillable = [
        'TAS_ID',
        'TAS_UID',
        'USR_UID',
        'TU_TYPE',
        'TU_RELATION'
    ];

    protected $attributes = [
        'TAS_ID' => '',
        'TAS_UID' => null,
        'USR_UID' => null,
        'TU_TYPE' => '',
        'TU_RELATION' => ''
    ];
    protected $casts = [
        'TAS_UID' => 'string',
        'USR_UID' => 'string',
        'TU_TYPE' => 'int',
        'TU_RELATION' => 'int'
    ];

    protected $rules = [
        'TAS_UID' => 'required|max:32',
        'USR_UID' => 'required|max:32',
        'TU_TYPE' => 'required',
        'TU_RELATION' => 'required'
    ];

    /**
     * @return MorphTo
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo('assignee', 'TU_RELATION', 'USR_ID');
    }

    /**
     * Get information user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'USR_UID', 'USR_UID');
    }

    /**
     * Get information user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function group(): HasOne
    {
        return $this->hasOne(Group::class, 'GRP_UID', 'USR_UID');
    }

    /**
     * Query only include Users
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnlyUsers(Builder $query): Builder
    {
        return $query->where('TU_RELATION', '=', User::TYPE);
    }

    /**
     * Query only include Groups
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeOnlyGroups(Builder $query): Builder
    {
        return $query->where('TU_RELATION', '=', Group::TYPE);
    }

    /**
     * Query only include of the type
     *
     * @param Builder $query
     * @param int $type
     *
     * @return Builder
     */
    public function scopeType(Builder $query, $type): Builder
    {
        return $query->where('TU_TYPE', '=', $type);
    }

}
