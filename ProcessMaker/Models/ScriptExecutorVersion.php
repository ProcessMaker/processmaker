<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;

class ScriptExecutorVersion extends ProcessMakerModel
{
    protected $fillable = [
        'title', 'description', 'language', 'config', 'draft',
    ];

    /**
     * Scope to only return draft versions.
     */
    public function scopeDraft(Builder $query)
    {
        return $query->where('draft', true);
    }

    /**
     * Scope to only return published versions.
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('draft', false);
    }
}
