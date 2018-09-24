<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use PMBinaryUuid;

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
