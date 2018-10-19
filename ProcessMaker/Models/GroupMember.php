<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 *  @OA\Schema(
 *   schema="group_membersEditable",
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="group_uuid", type="string", format="uuid"),
 *   @OA\Property(property="member_uuid", type="string", format="uuid"),
 *   @OA\Property(property="member_type", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="group_members",
 *   allOf={@OA\Schema(ref="#/components/schemas/group_membersEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 * 
 */
class GroupMember extends Model
{
    use HasBinaryUuid;

    protected $uuids = [
        'group_uuid', 'member_uuid'
    ];

    protected $fillable = [
        'group_uuid', 'member_uuid', 'member_type',
    ];

    public static function rules()
    {
        return [
            'group_uuid' => 'required',
            'member_uuid' => 'required',
            'member_type' => 'required|in:' . User::class . ',' . Group::class,
        ];

    }

    public function member()
    {
        return $this->morphTo(null, null, 'member_uuid');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

}
