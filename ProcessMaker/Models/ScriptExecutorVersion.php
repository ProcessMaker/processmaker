<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Enums\ScriptExecutorType;

class ScriptExecutorVersion extends ProcessMakerModel
{
    protected $fillable = [
        'title', 'description', 'language', 'config', 'draft', 'is_system', 'type',
    ];

    protected $casts = [
        'type' => ScriptExecutorType::class,
    ];

    /**
     * Scope to only return draft versions.
     */
    #[Scope]
    protected function draft(Builder $query)
    {
        return $query->where('draft', true);
    }

    /**
     * Scope to only return published versions.
     */
    #[Scope]
    protected function published(Builder $query)
    {
        return $query->where('draft', false);
    }
}
