<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Permission extends Model
{

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

    /**
     * Query filter for type = route
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoutes($query)
    {
        return $query->where('type', 'ROUTE');
    }
}
