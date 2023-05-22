<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class Permission extends ProcessMakerModel
{
    protected $connection = 'processmaker';

    protected $fillable = [
        'title',
        'name',
        'group',
    ];

    public function getResourceTitleAttribute()
    {
        $match = preg_match('/(.+)-(.+)/', $this->name, $matches);
        if ($match === 1) {
            return ucwords(preg_replace('/(\-|_)/', ' ', $matches[2]));
        }
    }

    public function getResourceNameAttribute()
    {
        $match = preg_match('/(.+)-(.+)/', $this->name, $matches);
        if ($match === 1) {
            return $matches[2];
        }
    }

    public function getActionAttribute()
    {
        $match = preg_match('/(.+)-(.+)/', $this->name, $matches);
        if ($match === 1) {
            return $matches[1];
        }
    }

    public static function resourceTitleList()
    {
        //Grab all of our permissions
        $all = self::all();

        $grouped = $all->groupBy('resource_title')->sort();

        return $grouped;
    }

    public static function resourceNameList()
    {
        //Grab all of our permissions
        $all = self::all();

        $grouped = $all->groupBy('resource_name');

        return $grouped;
    }

    public static function for($resource)
    {
        return self::byResource($resource)->pluck('name');
    }

    public static function byResource($resource)
    {
        //Grab all of our permissions
        $all = self::all();

        //Filter them by the name of the resource
        $filtered = $all->filter(function ($value, $key) use ($resource) {
            $match = preg_match("/(.+)-{$resource}/", $value->name);
            if ($match === 1) {
                return true;
            } else {
                return false;
            }
        });

        return $filtered;
    }

    public static function byName($name)
    {
        try {
            if (is_array($name)) {
                return self::whereIn('name', $name)->get();
            } else {
                return self::where('name', $name)->firstOrFail();
            }
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($name . ' permission does not exist');
        }
    }

    public function users()
    {
        return $this->morphedByMany('ProcessMaker\Models\User', 'assignable');
    }

    public function groups()
    {
        return $this->morphedByMany('ProcessMaker\Models\Group', 'assignable');
    }
}
