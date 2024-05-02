<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessMakerModel;
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
        $version = $this->versions()->create($attributes);

        // Delete draft version.
        try {
            $this->deleteDraft($this->alternative ?? null);
        } catch(QueryException $e) {
            // Skip delete if the screen version is used in a process.
        }

        return $version;
    }

    /**
     * Save a draft version of the model
     *
     * @param string|null $alternative Overwrite the alternative identifier.
     *
     * @return ProcessMakerModel
     */
    public function saveDraft(string $alternative = null)
    {
        $attributes = $this->getModelAttributes();
        $attributes['draft'] = true;
        if ($this->hasAlternative()) {
            $alternative = $alternative ?: $this->alternative;
            $attributes['alternative'] = $alternative;
        }

        return $this->versions()->updateOrCreate(
            [
                'draft' => true,
                ...(
                    $this->hasAlternative()
                    ? ['alternative' => $alternative]
                    : []
                ),
            ],
            $attributes
        );
    }

    public function deleteDraft(string $alternative = null)
    {
        $this->versions()->draft()
            ->when(
                $this->hasAlternative() && $alternative,
                function ($query) use ($alternative) {
                    $query->where('alternative', $alternative);
                }
            )->delete();
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
            $attributes['has_timer_start_events'],
            $attributes['projects']);

        return $attributes;
    }

    /**
     * Get the latest version of the executable artifact (screen, script)
     *
     * @param string $alternative The alternative version identifier. [A|B]
     *
     * @return ProcessMakerModel
     */
    public function getLatestVersion(string $alternative = 'A')
    {
        return $this->versions()
            ->when(
                $this->hasAlternative(),
                function ($query) use ($alternative) {
                    $query->where('alternative', $alternative);
                }
            )
            ->orderBy('id', 'desc')
            ->published()
            ->first();
    }

    /**
     * Get the latest version of the model (process, screen, script)
     *
     * @return ProcessMakerModel The published version of the artifact
     */
    public function getPublishedVersion(array $data)
    {
        $implementation = WorkflowManager::NAYRA_PUBLISHER . get_class($this);
        $existsCustomPublisher = $implementation
            && WorkflowManager::existsServiceImplementation($implementation);
        if ($existsCustomPublisher) {
            if ($data === []) {
                abort(
                    422,
                    __(
                        'Oops! It looks like there was an error setting up the variables for your A/B test. ' .
                        'Please contact the Administrator for assistance'
                    )
                );
            }

            $response = WorkflowManager::runServiceImplementation(
                $implementation,
                $data,
                [
                    'process' => $this,
                ],
            );

            return $response['publishedVersion'];
        }

        return $this->getLatestVersion();
    }

    /**
     * Get the latest version of artifact (screen, script)
     *
     * @param string $alternative The alternative version identifier. [A|B]
     *
     * @return ProcessMakerModel
     */
    public function getDraftOrPublishedLatestVersion(string $alternative = 'A')
    {
        return $this->versions()
            ->when(
                $this->hasAlternative(),
                function ($query) use ($alternative) {
                    $query->where('alternative', $alternative);
                }
            )
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Get the latest version of artifact (screen, script)
     *
     * @param string $alternative The alternative version identifier. [A|B]
     *
     * @return ProcessMakerModel
     */
    public function getDraftVersion(string $alternative = null)
    {
        $alternative = $alternative ?: ($this->alternative ?? null);

        return $this->versions()
            ->when(
                $this->hasAlternative(),
                function ($query) use ($alternative) {
                    $query->where('alternative', $alternative);
                }
            )
            ->draft()
            ->first();
    }

    /**
     * Return the version that was active when the task's request was started
     *
     * @param ProcessRequest $processRequest The process request object.
     *
     * @return ProcessMakerModel
     */
    public function versionFor(ProcessRequest $processRequest = null)
    {
        // Skip version locking for now
        // It will be re-added with more configurable options in a future version
        return $processRequest && $processRequest->process && $this instanceof Process
            ? $processRequest->process->getLatestVersion($processRequest->processVersion->alternative)
            : $this->getLatestVersion();
    }

    /**
     * Returns true if the model has alternatives.
     *
     * @return false
     */
    public function hasAlternative()
    {
        return false;
    }
}
