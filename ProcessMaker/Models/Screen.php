<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\Contracts\ScreenInterface;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasScreenFields;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\ProjectAssetTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Validation\CategoryRule;

/**
 * Class Screen
 *
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
 */
class Screen extends ProcessMakerModel implements ScreenInterface
{
    use SerializeToIso8601;
    use HideSystemResources;
    use HasCategories;
    use HasScreenFields;
    use HasVersioning;
    use ExtendedPMQL;
    use Exportable;
    use ProjectAssetTrait;

    const categoryClass = ScreenCategory::class;

    protected $connection = 'processmaker';

    /**
     * The table name attribute
     * @var string
     */
    protected $table = 'screens';

    protected $casts = [
        'config' => 'array',
        'computed' => 'array',
        'watchers' => 'array',
        'translations' => 'array',
    ];

    protected $appends = [
        'projects',
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
     * Table columns.
     *
     * @var array
     */
    protected $columns = [
        'id',
        'uuid',
        'screen_category_id',
        'title',
        'description',
        'type',
        'config',
        'computed',
        'custom_css',
        'created_at',
        'updated_at',
        'status',
        'key',
        'watchers',
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
            'screen_category_id' => [new CategoryRule($existing)],
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
     * Get the associated projects
     */
    public function projects()
    {
        return $this->belongsToMany('ProcessMaker\Package\Projects\Models\Project',
            'project_assets',
            'asset_id',
            'project_id',
            'id',
            'id'
        )->wherePivot('asset_type', static::class);
    }

    // Define the relationship with the ProjectAsset model
    public function projectAssets()
    {
        return $this->belongsToMany('ProcessMaker\Package\Projects\Models\ProjectAsset',
            'project_assets', 'asset_id', 'project_id')
            ->withPivot('asset_type')
            ->wherePivot('asset_type', static::class)->withTimestamps();
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
    public function nestedScreenIds(ProcessRequest $processRequest = null)
    {
        $screenIds = [];
        $screenFinder = new ScreensInScreen();
        $screenFinder->setProcessRequest($processRequest);
        foreach ($screenFinder->referencesToExport($this) as $screen) {
            $screenIds[] = $screen[1];
        }

        return $screenIds;
    }

    /**
     * PMQL value alias for fulltext field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasFullText($value, $expression)
    {
        return function ($query) use ($value) {
            $this->scopeFilter($query, $value);
        };
    }

    /**
     * Filter settings with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filterStr)
    {
        $filter = '%' . mb_strtolower($filterStr) . '%';
        $query->where(function ($query) use ($filter, $filterStr) {
            $query->where('screens.title', 'like', $filter)
                 ->orWhere('screens.description', 'like', $filter)
                 ->orWhere('screens.status', '=', $filterStr)
                 ->orWhereIn('screens.id', function ($qry) use ($filter) {
                     $qry->select('assignable_id')
                         ->from('category_assignments')
                         ->leftJoin('screen_categories', function ($join) {
                             $join->on('screen_categories.id', '=', 'category_assignments.category_id');
                             $join->where('category_assignments.category_type', '=', ScreenCategory::class);
                             $join->where('category_assignments.assignable_type', '=', self::class);
                         })
                         ->where('screen_categories.name', 'like', $filter);
                 });
        });

        return $query;
    }
}
