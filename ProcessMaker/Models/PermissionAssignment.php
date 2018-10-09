<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

class PermissionAssignment extends Model
{
    use HasBinaryUuid;
    
    protected $uuids = [
        'assignable_uuid', 'permission_uuid'
    ];

    public function assignable()
    {
        return $this->morphTo(null, null, 'assignable_uuid');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
