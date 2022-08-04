<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessRequest;

trait HasVersioning
{
    /**
     * Save a version every time the model is saved
     */
    public static function bootHasVersioning()
    {
        static::saved([static::class, 'saveNewVersion']);
    }

    /**
     * Save a new version of a model
     *
     * @param  Model  $model
     */
    public static function saveNewVersion($model)
    {
        $model->saveVersion();
    }

    /**
     * Save a version of the model
     */
    public function saveVersion()
    {
        $attributes = $this->attributesToArray();
        foreach ($this->hidden as $field) {
            $attributes[$field] = $this->$field;
        }
        unset($attributes['id'],
            $attributes['updated_at'],
            $attributes['created_at'],
            $attributes['has_timer_start_events']);
        $this->versions()->create($attributes);
    }

    /**
     * Get the latest version of the executable artifact (screen, script)
     */
    public function getLatestVersion()
    {
        return $this->versions()->orderBy('id', 'desc')->first();
    }

    /**
     * Return the version that was active when the task's request was started
     *
     * @param  ProcessRequestToken  $task
     * @return Model
     */
    public function versionFor(ProcessRequest $processRequest = null)
    {
        // Skip version locking for now
        // It will be re-added with more configurable options in a future version
        return $this->getLatestVersion();

        /*
        if (!$processRequest) {
            return $this->getLatestVersion();
        }

        $requestStartedAt = $processRequest->created_at;
        return self::versions()->where('created_at', '<=', $requestStartedAt)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        */
    }
}
