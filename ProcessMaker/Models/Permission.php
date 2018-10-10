<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;

class Permission extends Model
{
    use HasBinaryUuid;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];

    static public function byGuardName($name)
    {
        return self::where('guard_name', $name)->firstOrFail();
    }
}
