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
 * @property int task_id
 * @property int user_id
 * @property int type
 * @property string task_users_type
 */
class TaskUser extends Model
{
    use ValidatingTrait;

    public $timestamps = false;

    /**
     * values for type
     */
    const ASSIGNEE_NORMAL = 1;
    const ASSIGNEE_ADHOC = 2;

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'task_users_type'
    ];

    protected $attributes = [
        'task_id' => null,
        'user_id' => null,
        'type' => self::ASSIGNEE_NORMAL,
        'task_users_type' => User::TYPE
    ];
    protected $casts = [
        'task_id' => 'int',
        'user_id' => 'int',
        'type' => 'int',
        'task_users_type' => 'string'
    ];

    protected $rules = [
        'task_id' => 'exists:tasks,id',
        'user_id' => 'required',
        'type' => 'required|in:' . self::ASSIGNEE_NORMAL . ',' . self::ASSIGNEE_ADHOC,
        'task_users_type' => 'required|in:' . User::TYPE . ',' . Group::TYPE
    ];

    /**
     * Get information user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Get information user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function group(): HasOne
    {
        return $this->hasOne(Group::class, 'id', 'user_id');
    }

    /**
     * Query only include Users
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnlyUsers(Builder $query): Builder
    {
        return $query->where('task_users_type', '=', User::TYPE);
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
        return $query->where('task_users_type', '=', Group::TYPE);
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
        return $query->where('type', '=', $type);
    }

}
