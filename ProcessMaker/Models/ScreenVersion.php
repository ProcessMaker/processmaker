<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Contracts\ScreenInterface;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasScreenFields;

class ScreenVersion extends ProcessMakerModel implements ScreenInterface
{
    use HasCategories;
    use HasScreenFields;

    const categoryClass = ScreenCategory::class;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'updated_at',
    ];

    protected $casts = [
        'config' => 'array',
        'computed' => 'array',
        'watchers' => 'array',
        'translations' => 'array',
    ];

    /**
     * Set multiple|single categories to the screen
     *
     * @param string $value
     */
    public function setScreenCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'screen_category_id');
    }

    /**
     * Get the associated screen
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Screen::class, 'screen_id', 'id');
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
