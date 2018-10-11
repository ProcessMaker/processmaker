<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents a group definition.
 *
 * @property string $uuid
 * @property string $name
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * 
 *   @OA\Schema(
 *   schema="groupsEditable",
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="groups",
 *   allOf={@OA\Schema(ref="#/components/schemas/groupsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Group extends Model
{
    use HasBinaryUuid;

    protected $fillable = [
        'name',
    ];

    public static function rules($existing = null)
    {
        $rules = [
            'name' => 'required|string|unique:groups,name'
        ];

        if ($existing) {
            $rules['name'] = [
                'required',
                'string',
                Rule::unique('groups')->ignore($existing->uuid)
            ];
        }

        return $rules;
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }
    
    public function memberships()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_uuid');
    }
}
