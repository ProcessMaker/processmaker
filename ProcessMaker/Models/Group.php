<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Query\Traits\PMQL;

/**
 * Represents a group definition.
 *
 * @property string $id
 * @property string $name
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="groupsEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
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
 *
 */
class Group extends Model
{
    use PMQL;
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('groups')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:255', $unique, 'alpha_spaces'],
            'status' => 'in:ACTIVE,INACTIVE'
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
    
    public function getRecursiveUsersAttribute(Group $parent = null)
    {
        // Parent is used to determine the top level group in order to prevent
        // infinite loops in the case of two groups nested within each other
        if (! $parent) {
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
}
