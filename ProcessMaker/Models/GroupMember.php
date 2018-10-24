<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *  @OA\Schema(
 *   schema="group_membersEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="group_id", type="string", format="id"),
 *   @OA\Property(property="member_id", type="string", format="id"),
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
