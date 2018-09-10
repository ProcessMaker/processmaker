<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;


class GroupMember extends Model
{
    use HasBinaryUuid;

    protected $fillable = [
        'group_uuid', 'member_uuid', 'member_type',
    ];

    public static function rules()
    {
        return [
            'group_uuid' => 'required|string',
            'member_uuid' => 'required|string',
            'member_type' => 'required|in:user,group',
        ];

    }

    public function member()
    {
        return $this->morphTo();
    }

    public function group()
    {
        return $this->belongsTo('ProcessMaker\Models\Group','group_uuid');
    }

}
