<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

class PermissionAssignment extends Model
{
    use HasBinaryUuid;

    public function assignable()
    {
        return $this->morphTo(null, null, 'assignable_uuid');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
