<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasVersioning
{
    /**
     * Save a version every time the model is saved
     *
     */
    public static function bootHasVersioning()
    {
        static::saved([static::class, 'saveNewVersion']);
    }

    /**
     * Save a new version of a model
     *
     * @param Model $model
     */
    public static function saveNewVersion($model)
    {
        $model->saveVersion();
    }

    /**
     * Save a version of the model
     *
     */
    public function saveVersion()
    {
        $attributes = $this->attributesToArray();
        unset($attributes['id'],
        $attributes['updated_at'],
        $attributes['created_at'],
        $attributes['has_timer_start_events']);
        $this->versions()->create($attributes);
    }

    /**
     * Get the latest version of the executable artifact (screen, script)
     *
     */
    public function getLatestVersion()
    {
        return $this->versions()->orderBy('id', 'desc')->first();
    }
}
