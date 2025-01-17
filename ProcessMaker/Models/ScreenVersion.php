<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Contracts\PrometheusMetricInterface;
use ProcessMaker\Contracts\ScreenInterface;
use ProcessMaker\Events\TranslationChanged;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasScreenFields;

class ScreenVersion extends ProcessMakerModel implements ScreenInterface, PrometheusMetricInterface
{
    use HasCategories;
    use HasScreenFields;

    const categoryClass = ScreenCategory::class;

    protected $connection = 'processmaker';

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
     * Boot the model and its events
     */
    public static function boot()
    {
        parent::boot();
    }

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

    public function getPrometheusMetricLabel(): string
    {
        return 'screen.' . $this->screen_id;
    }
}
