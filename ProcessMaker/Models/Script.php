<?php

namespace ProcessMaker\Models;

use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use ProcessMaker\Contracts\ScriptInterface;
use ProcessMaker\Exception\RetryableException;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptRunner;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;
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
 * )
 */
class Script extends ProcessMakerModel implements ScriptInterface
{
    use SerializeToIso8601;
    use HideSystemResources;
    use HasCategories;
    use HasVersioning;
    use Exportable;

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

    private $errorHandling = [];

    private $attemptedRetries = 1;

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
            'retry_attempts' => 'integer|min:0|max:65535',
            'retry_wait_time' => 'integer|min:0|max:65535',
            'script_category_id' => [new CategoryRule($existing)],
        ];
    }

    /**
     * Executes a script given a configuration and data input.
     *
     * @param array $data
     * @param array $config
     */
    public function runScript(array $data, array $config, $tokenId = '', $errorHandling = [])
    {
        if (!$this->scriptExecutor) {
            throw new ScriptLanguageNotSupported($this->language);
        }
        $this->errorHandling = $errorHandling;
        $runner = new ScriptRunner($this->scriptExecutor);
        $runner->setTokenId($tokenId);
        $user = User::find($this->run_as_user_id);
        if (!$user) {
            throw new \RuntimeException('A user is required to run scripts');
        }

        try {
            return $runner->run($this->code, $data, $config, $this->timeout(), $user);
        } catch (ScriptException | \RuntimeException $e) {
            \Log::info('----', [$this->retryAttempts(), $this->retryWaitTime()]);
            if ($this->retryAttempts()) {
                Log::info('Retry the runScript process. Attempt ' . $this->attemptedRetries . ' of ' . $this->retryAttempts());
                if ($this->attemptedRetries > $this->retryAttempts()) {
                    $message = __('Script failed after :attempts total attempts', ['attempts' => $this->attemptedRetries]);
                    $message = $message . "\n" . $e->getMessage();
                    // Log::error($message);
                    if ($e instanceof ScriptTimeoutException) {
                        throw new ScriptTimeoutException($message);
                    }
                    throw new ScriptException($message);
                }

                if (is_numeric($this->retryWaitTime())) {
                    Log::info("Waiting {$this->retryWaitTime()} seconds before retrying.");
                    sleep($this->retryWaitTime());
                }
                $this->attemptedRetries++;

                Log::info('Re-running the script', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
                // $result = $this->runScript($data, $config, $tokenId, $errorHandling);
                // RunScriptTask::dispatch($process, $instance, $token, []);
                throw new RetryableException();
                Log::info('The script completed successfully');

                return $result;
            } else {
                throw $e;
            }
        }
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
     * This method retrieves the error number by type.
     * In case of not finding it, -1 is returned.
     */
    private function getErrorHandlingNumberByType($type)
    {
        $result = Arr::get($this->errorHandling, $type, null);
        if (is_numeric($result)) {
            return (int) $result;
        }

        return -1;
    }

    /**
     * Get the value of the timeout property if there hasn't been a defined error in the errorHandling array.
     */
    private function timeout()
    {
        $result = $this->getErrorHandlingNumberByType('timeout');

        return $result === -1 ? $this->timeout : $result;
    }

    /**
     * Get the value of the retryAttempts property if there hasn't been a defined error in the errorHandling array.
     */
    private function retryAttempts()
    {
        $result = $this->getErrorHandlingNumberByType('retry_attempts');

        return $result === -1 ? $this->retry_attempts : $result;
    }

    /**
     * Get the value of the retryWaitTime property if there hasn't been a defined error in the errorHandling array.
     */
    private function retryWaitTime()
    {
        $result = $this->getErrorHandlingNumberByType('retry_wait_time');

        return $result === -1 ? $this->retry_wait_time : $result;
    }
}
