<?php

namespace ProcessMaker\Models;

/**
 * Represents a group Members definition.
 *
 * @property int $id
 * @property int $group_id
 * @property string $member_type
 * @property int $member_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 *
 * @OA\Schema(
 *     schema="groupMembersEditable",
 *     @OA\Property(property="group_id", type="string", format="id"),
 *     @OA\Property(property="member_id", type="string", format="id"),
 *     @OA\Property(property="member_type", type="string"),
 *     @OA\Property(property="description", type="string"),
 * ),
 * @OA\Schema(
 *     schema="groupMembers",
 *     allOf={
 *      @OA\Schema(ref="#/components/schemas/groupMembersEditable"),
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          )
 *      },
 * ),
 *  @OA\Schema(
 *     schema="createGroupMembers",
 *     allOf={
 *      @OA\Schema(ref="#/components/schemas/groupMembersEditable"),
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="group", type="object"),
 *          @OA\Property(property="member", type="object"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          )
 *      },
 * ),
 * @OA\Schema(
 *     schema="getGroupMembersById",
 *     allOf={
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="group_id", type="string", format="id"),
 *          @OA\Property(property="member_id", type="string", format="id"),
 *          @OA\Property(property="member_type", type="string"),
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          )
 *      },
 * ),
 * @OA\Schema(
 *     schema="availableGroupMembers",
 *     allOf={
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="description", type="string"),
 *          @OA\Property(property="name", type="string"),
 *          @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *          )
 *      },
 * ),
 */
class GroupMember extends ProcessMakerModel
{
    protected $fillable = [
        'group_id', 'member_id', 'member_type',
    ];

    public static function rules()
    {
        return [
            'group_id' => 'required',
            'member_id' => 'required',
            'member_type' => 'required|in:' . User::class . ',' . Group::class,
        ];
    }

    public function member()
    {
        return $this->morphTo(null, null, 'member_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
