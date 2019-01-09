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
    
    static public function for($resource)
    {
        return self::byResource($resource)->pluck('name');
    }
    
    static public function byResource($resource)
    {
        //Grab all of our permissions
        $all = self::all();
        
        //Filter them by the name of the resource
        $filtered = $all->filter(function ($value, $key) use($resource) {
            $match = preg_match("/(.+)-{$resource}/", $value->name);
            if ($match === 1) {
                return true;
            } else {
                return false;
            }
        });
        
        return $filtered;
    }

    static public function byName($name)
    {
        try {
            return self::where('name', $name)->firstOrFail();
        } catch(ModelNotFoundException $e) {
            throw new ModelNotFoundException($name . " permission does not exist");
        }
    }
}
