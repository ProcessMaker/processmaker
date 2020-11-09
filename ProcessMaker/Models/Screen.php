<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasScreenFields;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Validation\CategoryRule;
use ProcessMaker\Assets\ScreensInScreen;

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
 * @property array watchers
 * @property string label
 * @property Carbon type
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @OA\Schema(
 *   schema="screensEditable",
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="config", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="computed", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="watchers", type="array", @OA\Items(type="object")),
 *   @OA\Property(property="custom_css", type="string"),
 *   @OA\Property(property="screen_category_id", type="string"),
 * ),
 * @OA\Schema(
 *   schema="screens",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/screensEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="string", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     ),
 *   },
 * )
 *
 * @OA\Schema(
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
    use HasScreenFields;
    use HasVersioning;
    use ExtendedPMQL;

    const categoryClass = ScreenCategory::class;

    protected $connection = 'processmaker';

    protected $casts = [
        'config' => 'array',
        'computed' => 'array',
        'watchers' => 'array',
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
            'title' => ['required', $unique, 'alpha_spaces'],
            'description' => 'required',
            'type' => 'required',
            'screen_category_id' => [new CategoryRule($existing)]
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
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }

    public function builderComponent()
    {
        if (isset($this->config['builderComponent'])) {
            return $this->config['builderComponent'];
        }
        return 'ScreenBuilder';
    }
    
    public function renderComponent()
    {
        if (isset($this->config['renderComponent'])) {
            return $this->config['renderComponent'];
        }
        return 'task-screen';
    }

    /**
     * PMQL field (id = screens.id)
     *
     * @return string
     */
    public function fieldAliasId()
    {
        return 'screens.id';
    }

    /**
     * Get a recursive list of nested screens IDs in this screen
     * 
     * @return int[] nested screen IDs
     */
    public function nestedScreenIds()
    {
        $screenIds = [];
        $screenFinder = new ScreensInScreen();
        foreach($screenFinder->referencesToExport($this) as $screen) {
            $screenIds[] = $screen[1];
        }
        return $screenIds;
    }
}
