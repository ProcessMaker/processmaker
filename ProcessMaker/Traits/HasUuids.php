<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Str;

trait HasUuids
{
    /**
     * Add a UUID to the model.
     *
     * @return void
     */
    public static function bootHasUuids(): void
    {
        // TODO: Remove call in collections package src/Observers/RecordObserver.php
        static::creating(function ($model) {
            if (get_class($model) !== 'ProcessMaker\\Plugins\\Collections\\Models\\Record' && !isset($model->uuid)) {
                $model->uuid = self::generateUuid();
            }
        });
    }

    /**
     * Generate an ordered UUID
     */
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }
}
