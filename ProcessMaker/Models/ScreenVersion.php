<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Contracts\ScreenInterface;
use ProcessMaker\Traits\HasCategories;

class ScreenVersion extends Model implements ScreenInterface
{
    use HasCategories;

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
    ];

    /**
     * Set multiple|single categories to the screen
     *
     * @param  string  $value
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
}
