<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\HideSystemResources;

/**
 * Class Screen
 *
 * @package ProcessMaker\Models
 *
 * @property string id
 * @property string title
 * @property string description
 * @property array content
 * @property array config
 * @property array computed
 * @property array custom_css
 * @property string label
 * @property Carbon type
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="screensEditable",
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="config", type="string"),
 *   @OA\Property(property="computed", type="string"),
 *   @OA\Property(property="custom_css", type="string"),
 *   @OA\Property(property="screen_category_id", type="string"),
 * ),
 * @OA\Schema(
 *   schema="screens",
 *   allOf={@OA\Schema(ref="#/components/schemas/screensEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 * 
 * * @OA\Schema(
 *   schema="screenExported",
 *   @OA\Property(property="url", type="string"),
 * )
 *
 */
class Screen extends Model
{
    use SerializeToIso8601;
    use HideSystemResources;
    use HasCategories;

    const categoryClass = ScreenCategory::class;

    protected $connection = 'processmaker';

    protected $casts = [
        'config' => 'array',
        'computed' => 'array'
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('screens')->ignore($existing);

        return [
            'title' => ['required', $unique],
            'description' => 'required',
            'type' => 'required'
        ];
    }

    /**
     * Get the associated versions
     */
    public function versions()
    {
        return $this->hasMany(ScreenVersion::class);
    }

    /**
     * Get the associated category
     */
    public function category()
    {
        return $this->belongsTo(ScreenCategory::class, 'screen_category_id');
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
     * Get multiple|single categories of the screen
     *
     * @param string $value
     */
    public function getScreenCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray());
    }
}
