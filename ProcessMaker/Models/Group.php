<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\EmptyModel;
use ProcessMaker\Query\Traits\PMQL;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a group definition.
 *
 * @property string $id
 * @property string $descripcion
 * @property User $manager
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="groupsEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="manager_id", type="int", format="id"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="groups",
 *   allOf={
 *      @OA\Schema(ref="#/components/schemas/groupsEditable"),
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          @OA\Property(property="id", type="string", format="id"),
 *      )
 *   }
 * )
 */
class Group extends ProcessMakerModel
{
    use PMQL;
    use SerializeToIso8601;
    use Exportable;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'description',
        'manager_id',
        'status',
        'enabled_2fa',
    ];

    protected $casts = [
        'enabled_2fa' => 'boolean',
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('groups')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:255', $unique, 'alpha_spaces'],
            'status' => 'in:ACTIVE,INACTIVE',
        ];
    }

    public function permissions()
    {
        return $this->morphToMany('ProcessMaker\Models\Permission', 'assignable');
    }

    public function processesFromProcessable()
    {
        return $this->morphToMany('ProcessMaker\Models\Process', 'processable');
    }

    public function groupMembersFromMemberable()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_id');
    }

    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function getUsersAttribute()
    {
        return $this->groupMembers->where('member_type', User::class)->map(function ($member) {
            return $member->member;
        });
    }

    /**
     * Manager of the group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function projectMembers()
    {
        if (class_exists('ProcessMaker\Package\Projects\Models\ProjectMember')) {
            return $this->hasMany('ProcessMaker\Package\Projects\Models\ProjectMember', 'member_id', 'id')->where('member_type', self::class);
        } else {
            // Handle the case where the ProjectMember class doesn't exist.
            return $this->hasMany(EmptyModel::class);
        }
    }

    public function getRecursiveUsersAttribute(self $parent = null)
    {
        // Parent is used to determine the top level group in order to prevent
        // infinite loops in the case of two groups nested within each other
        if (!$parent) {
            $parent = $this;
        }

        $users = collect();

        $users = $users->merge($this->users);

        $this->groupMembers->where('member_type', self::class)->each(function ($member) use (&$users, $parent) {
            if ($member->member->id != $parent->id) {
                $users = $users->merge($member->member->getRecursiveUsersAttribute($parent));
            }
        });

        return $users->unique(function ($user) {
            return $user->id;
        })->sortBy('id')->values();
    }

    /**
     * Scope to only return active groups.
     *
     * @var Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Group as assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function assigned()
    {
        return $this->morphMany(ProcessTaskAssignment::class, 'assigned', 'assignment_type', 'assignment_id');
    }

    /**
     * Parent group IDs for the given groups (one walk up, not one query per group).
     */
    public static function ancestorIdsFor(iterable $groupIds): Collection
    {
        $ids = collect($groupIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return collect(static::computeAncestorIds($ids->all()));
    }

    public const SELF_SERVICE_HIERARCHY_VERSION_KEY = 'self_service:hierarchy_version';

    public static function selfServiceHierarchyVersion(): int
    {
        return (int) Cache::get(self::SELF_SERVICE_HIERARCHY_VERSION_KEY, 1);
    }

    public static function bumpSelfServiceHierarchyVersion(): void
    {
        if (!Cache::has(self::SELF_SERVICE_HIERARCHY_VERSION_KEY)) {
            Cache::forever(self::SELF_SERVICE_HIERARCHY_VERSION_KEY, 1);
        }
        Cache::increment(self::SELF_SERVICE_HIERARCHY_VERSION_KEY);
    }

    /**
     * @return array<int>
     */
    private static function computeAncestorIds(array $groupIds): array
    {
        $ancestors = [];
        $queue = $groupIds;
        $visited = array_fill_keys($groupIds, true);

        while ($queue) {
            $parents = GroupMember::query()
                ->where('member_type', self::class)
                ->whereIn('member_id', $queue)
                ->pluck('group_id')
                ->all();

            $queue = [];
            foreach ($parents as $parentId) {
                $parentId = (int) $parentId;
                if (isset($visited[$parentId])) {
                    continue;
                }
                $visited[$parentId] = true;
                $ancestors[] = $parentId;
                $queue[] = $parentId;
            }
        }

        return $ancestors;
    }
}
