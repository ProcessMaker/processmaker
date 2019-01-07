<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Permission extends Model
{

    protected $fillable = [
        'title',
        'name',
    ];

    static public function byName($name)
    {
        try {
            return self::where('name', $name)->firstOrFail();
        } catch(ModelNotFoundException $e) {
            throw new ModelNotFoundException($name . " permission does not exist");
        }
    }
}
