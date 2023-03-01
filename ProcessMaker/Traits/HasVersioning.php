<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

trait HasVersioning
{
    /**
     * The "boot" method of HasVersioning.
     */
    public static function bootHasVersioning()
    {
        static::addGlobalScope('published', function (Builder $builder) {
            $builder->published();
        });

        // Save a version every time the model is saved.
        static::saved([static::class, 'saveNewVersion']);
    }

    /**
     * Get the published for the model.
     */
    public function scopePublished($query)
    {
        $query->whereHas('versions', function ($query) {
            // Avoid migration errors when 'draft' column does not exist.
            $hasDraftColumn = Schema::hasColumn($query->getModel()->getTable(), 'draft');
            $query->when($hasDraftColumn, function ($query) {
                $query->where('draft', false);
            });
        });
    }

    /**
     * Save a new version of a model
     */
    public static function saveNewVersion(Model $model)
    {
        $model->saveVersion();
    }

    /**
     * Save a published version of the model.
     */
    public function saveVersion()
    {
        $attributes = $this->getModelAttributes();
        $this->versions()->create($attributes);

        // Delete draft version.
        $this->deleteDraft();
    }

    /**
     * Save a draft version of the model
     */
    public function saveDraft()
    {
        $attributes = $this->getModelAttributes();
        $attributes['draft'] = true;

        $this->versions()->updateOrCreate([
            'draft' => true,
        ], $attributes);
    }

    public function deleteDraft()
    {
        $this->versions()->draft()->delete();
    }

    private function getModelAttributes(): array
    {
        $attributes = $this->attributesToArray();
        foreach ($this->hidden as $field) {
            $attributes[$field] = $this->$field;
        }
        unset($attributes['id'],
            $attributes['uuid'],
            $attributes['updated_at'],
            $attributes['created_at'],
            $attributes['has_timer_start_events']);

        return $attributes;
    }

    /**
     * Get the latest version of the executable artifact (screen, script)
     */
    public function getLatestVersion()
    {
        return $this->versions()->orderBy('id', 'desc')->published()->first();
    }

    /**
     * Return the version that was active when the task's request was started
     *
     * @param ProcessRequestToken $task
     * @return ProcessMakerModel
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
