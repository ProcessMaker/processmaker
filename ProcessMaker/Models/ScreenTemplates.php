<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HideSystemResources;

class ScreenTemplates extends Template
{
    use HasFactory;
    use HasCategories;
    use HideSystemResources;

    protected $table = 'screen_templates';

    const categoryClass = ScreenCategory::class;

    public $screen_category_id;

    /**
     * Category of the screen template
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ScreenCategory::class, 'screen_category_id')->withDefault();
    }

    /**
     * Set multiple|single categories to the screen template
     *
     * @param string $value
     */
    public function setScreenCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'screen_category_id');
    }

    /**
     * Get multiple|single categories of the screen template
     *
     * @param string $value
     */
    public function getScreenCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }
}
