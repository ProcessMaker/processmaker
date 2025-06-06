<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Contracts\ScriptInterface;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\ImportExport\Exporters\ScriptExporter;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptRunner;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\ExtendedPMQL;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\ProjectAssetTrait;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Validation\CategoryRule;

/**
 * Represents an Eloquent model of a Script
 *
 *
 * @property int id
 * @property string key
 * @property string title
 * @property text description
 * @property string language
 * @property text code
 * @property int timeout
 *
 * @OA\Schema(
 *   schema="scriptsEditable",
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="language", type="string"),
 *   @OA\Property(property="code", type="string"),
 *   @OA\Property(property="timeout", type="integer"),
 *   @OA\Property(property="run_as_user_id", type="integer"),
 *   @OA\Property(property="key", type="string"),
 *   @OA\Property(property="script_category_id", type="integer"),
 * ),
 * @OA\Schema(
 *   schema="scripts",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/scriptsEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="integer", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     )
 *   },
 * ),
 *
 * @OA\Schema(
 *   schema="scriptsPreview",
 *   @OA\Property(property="status", type="string"),
 *   @OA\Property(property="key", type="string"),
 *   @OA\Property(property="output", type="object"),
 * )
 */
class Script extends ProcessMakerModel implements ScriptInterface
{
    use SerializeToIso8601;
    use HideSystemResources;
    use HasCategories;
    use HasVersioning;
    use Exportable;
    use ProjectAssetTrait;
    use ExtendedPMQL;

    const categoryClass = ScriptCategory::class;

    const deprecatedLanguages = ['lua', 'r'];

    protected $connection = 'processmaker';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
        'retry_wait_time' => 'integer',
    ];

    protected $appends = [
        'projects',
    ];

    /**
     * Override the default boot method to allow access to lifecycle hooks
     *
     * @return null
     */
    public static function boot()
    {
        parent::boot();

        $clearCacheCallback = function () {
            self::clearAndRebuildUserProjectAssetsCache();
        };

        self::saving(function ($script) use ($clearCacheCallback) {
            // If a script executor has not been set, choose one
            // automatically based on the scripts set language
            $script->setDefaultExecutor();

            // Execute the clear cache callback
            $clearCacheCallback($script);
        });

        static::updating($clearCacheCallback);
        static::deleting($clearCacheCallback);
    }

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('scripts')->ignore($existing);

        if ($existing) {
            $allowedLanguages = static::scriptFormatValues();
            $allowedExecutorIds = ScriptExecutor::all()->pluck('id')->toArray();
        } else {
            $allowedLanguages = array_filter(static::scriptFormatValues(), function ($language) {
                return !in_array($language, Script::deprecatedLanguages);
            });
            $allowedExecutorIds = ScriptExecutor::active()->pluck('id')->toArray();
        }

        return [
            'key' => 'unique:scripts,key',
            'title' => ['required', 'string', $unique, 'alpha_spaces'],
            'language' => [
                'required_without:script_executor_id',
                Rule::in($allowedLanguages),
            ],
            'script_executor_id' => [
                'required_without:language',
                Rule::in($allowedExecutorIds),
            ],
            'description' => 'required',
            'run_as_user_id' => 'required',
            'timeout' => 'integer|min:0|max:65535',
            'script_category_id' => [new CategoryRule($existing)],
        ];
    }

    /**
     * Executes a script given a configuration and data input.
     *
     * @param array $data
     * @param array $config
     */
    public function runScript(array $data, array $config, $tokenId = '', $timeout = null, $sync = 1, $metadata = [])
    {
        if (!$timeout) {
            $timeout = $this->timeout;
        }

        if (!$this->scriptExecutor) {
            throw new ScriptLanguageNotSupported($this->language);
        }
        $runner = new ScriptRunner($this);
        $runner->setTokenId($tokenId);
        $user = User::find($this->run_as_user_id);
        if (!$user) {
            throw new ConfigurationException('A user is required to run scripts');
        }

        return $runner->run($this->code, $data, $config, $timeout, $user, $sync, $metadata);
    }

    /**
     * Get a configuration array of all supported script formats.
     *
     * @return array
     */
    public static function scriptFormats()
    {
        return config('script-runners');
    }

    /**
     * Get the configuration for a specific script format.
     *
     * @param string $format
     *
     * @return array
     */
    public static function scriptFormat($format)
    {
        $formats = static::scriptFormats();

        if (array_key_exists($format, $formats)) {
            return $formats[$format];
        } else {
            return null;
        }
    }

    /**
     * Get a basic array of supported script formats.
     *
     * @return array
     */
    public static function scriptFormatValues()
    {
        $values = [];
        $formats = static::scriptFormats();

        foreach ($formats as $key => $format) {
            $values[] = $key;
        }

        // If "php" format is included also include "php-nayra"
        if (in_array('php', $values)) {
            $values[] = 'php-nayra';
        }

        return $values;
    }

    /**
     * Get a key/value pair array of supported script formats.
     *
     * @return array
     */
    public static function scriptFormatList()
    {
        $list = [];
        $formats = static::scriptFormats();

        foreach ($formats as $key => $format) {
            $list[$key] = $format['name'];
        }

        return $list;
    }

    /**
     * Get the language from a script format (MIME type) string.
     *
     * @param string $mimeType
     *
     * @return string
     */
    public static function scriptFormat2Language($mimeType)
    {
        $formats = static::scriptFormats();

        foreach ($formats as $key => $format) {
            if ($mimeType == $format['mime_type']) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get the language name for this script.
     *
     * @return string
     */
    public function getLanguageNameAttribute()
    {
        if ($format = static::scriptFormat($this->language)) {
            return $format['name'];
        } else {
            return $this->language;
        }
    }

    /**
     * Get the associated versions
     */
    public function versions()
    {
        return $this->hasMany(ScriptVersion::class);
    }

    /**
     * Get the associated run_as_user
     */
    public function runAsUser()
    {
        return $this->belongsTo(User::class, 'run_as_user_id');
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
     * Return the a user for service tasks
     *
     * @return ProcessMaker\Models\User
     */
    public static function defaultRunAsUser()
    {
        // return the default admin user
        return User::where('is_administrator', true)->firstOrFail();
    }

    /**
     * Get the associated category
     */
    public function category()
    {
        return $this->belongsTo(ScriptCategory::class, 'script_category_id');
    }

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
     * Get multiple|single categories of the script
     *
     * @param string $value
     */
    public function getScriptCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }

    /**
     * Get the associated executor
     */
    public function scriptExecutor()
    {
        return $this->belongsTo(ScriptExecutor::class, 'script_executor_id');
    }

    /**
     * Save the default executor when only a language is specified
     */
    private function setDefaultExecutor()
    {
        if (empty($this->script_executor_id)) {
            $initialExecutor = ScriptExecutor::initialExecutor($this->language);
            if ($initialExecutor) {
                $this->script_executor_id = $initialExecutor->id;
            }
        }
        if (empty($this->language)) {
            $this->language = $this->scriptExecutor->language;
        }
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
            $query->where('scripts.title', 'like', $filter)
                 ->orWhere('scripts.description', 'like', $filter)
                 ->orWhere('scripts.status', '=', $filterStr)
                 ->orWhereIn('scripts.id', function ($qry) use ($filter) {
                     $qry->select('assignable_id')
                         ->from('category_assignments')
                         ->leftJoin('script_categories', function ($join) {
                             $join->on('script_categories.id', '=', 'category_assignments.category_id');
                             $join->where('category_assignments.category_type', '=', ScriptCategory::class);
                             $join->where('category_assignments.assignable_type', '=', self::class);
                         })
                         ->where('script_categories.name', 'like', $filter);
                 });
        });

        return $query;
    }
}
