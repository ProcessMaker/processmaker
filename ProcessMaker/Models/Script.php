<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Contracts\ScriptInterface;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Managers\WorkflowManagerRabbitMq;
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
 * @property string description
 * @property string language
 * @property string code
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
        self::saving(function ($script) {
            // If a script executor has not been set, choose one
            // automatically based on the scripts set language
            $script->setDefaultExecutor();
        });
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

        return [
            'key' => 'unique:scripts,key',
            'title' => ['required', 'string', $unique, 'alpha_spaces'],
            'language' => [
                'required_without:script_executor_id',
                Rule::in(static::scriptFormatValues()),
            ],
            'script_executor_id' => 'required_without:language|exists:script_executors,id',
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
    public function runScript(array $data, array $config, $tokenId = '', $timeout = null)
    {
        if (!$timeout) {
            $timeout = $this->timeout;
        }

        if (!$this->scriptExecutor) {
            throw new ScriptLanguageNotSupported($this->language);
        }

        $trace = "";
        // foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4) as $t) {
        //     $trace .= "    " . $t['file'] . ":" . $t['line'] . "\n";
        // }
        // log last 4 traces
        \Log::error("Script ID={$this->id} Token ID={$tokenId}\n" . $trace);
        // if (!$this->id) {
        //     \Log::error($this->code);
        // }

        $useNayraDocker = !empty(config('app.nayra_rest_api_host')) && !empty($this->id);
        if ($useNayraDocker) {
            return $this->callNayraRunScript($this->code, $data, $config);
        }

        $runner = new ScriptRunner($this->scriptExecutor);
        $runner->setTokenId($tokenId);
        $user = User::find($this->run_as_user_id);
        if (!$user) {
            throw new ConfigurationException('A user is required to run scripts');
        }

        return $runner->run($this->code, $data, $config, $timeout, $user);
    }

    public function callNayraRunScript(string $code, array $data, array $config)
    {
        $engine = new WorkflowManagerRabbitMq();
        $params = [
            'name' => uniqid('script_', true),
            'script' => $code,
            'data' => $data,
            'config' => $config,
            'envVariables' => $engine->getEnvironmentVariables(),
        ];
        $body = json_encode($params);
        $url = config('app.nayra_rest_api_host') . '/run_script';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            return [
                'error' => curl_error($ch)
            ];
        }
        return json_decode($result, true);
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
            $this->script_executor_id = ScriptExecutor::initialExecutor($this->language)->id;
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
