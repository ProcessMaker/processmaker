<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\HasCategories;

class ScreenVersion extends Model
{
    use HasCategories;

    const categoryClass = ScreenCategory::class;

    protected $connection = 'processmaker';

    /**
     * Do not automatically set created_at
     */
    const CREATED_AT = null;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $fillable
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
     * @param string $value
     */
    public function setScreenCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'screen_category_id');
    }

}
