<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionAssignment extends Model
{
    use HasBinaryUuid;
    protected $fillable = [
        'permission_id',
        'assignable_id',
        'assignable_type',
    ];

    public function assignable()
    {
        return $this->morphTo(null, null, 'assignable_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
