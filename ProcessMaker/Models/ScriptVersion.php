<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Contracts\ScriptInterface;
use ProcessMaker\Traits\HasCategories;

class ScriptVersion extends ProcessMakerModel implements ScriptInterface
{
    use HasCategories;

    const categoryClass = ScriptCategory::class;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'updated_at',
    ];

    /**
     * Set multiple|single categories to the script
     *
     * @param string $value
     */
    public function setScriptCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'script_category_id');
    }

    /**
     * Get the associated script
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Script::class, 'script_id', 'id');
    }

    /**
     * Executes a script given a configuration and data input.
     *
     * @param array $data
     * @param array $config
     */
    public function runScript(array $data, array $config, $tokenId = '', $timeout = null)
    {
        $script = $this->parent->replicate();
        $except = $script->getGuarded();
        foreach (collect($script->getAttributes())->except($except)->keys() as $prop) {
            $script->$prop = $this->$prop;
        }

        return $script->runScript($data, $config, $tokenId, $timeout);
    }

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
