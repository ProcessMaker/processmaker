<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BinaryUuid\HasBinaryUuid;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        try {
            return self::where('guard_name', $name)->firstOrFail();
        } catch(ModelNotFoundException $e) {
            throw new ModelNotFoundException($name . " permission does not exist");
        }
    }
}
