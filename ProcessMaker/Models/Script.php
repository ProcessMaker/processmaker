<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptRunner;
use ProcessMaker\Models\ScriptCategory;

/**
 * Represents an Eloquent model of a Script
 *
 * @package ProcessMaker\Model
 *
 * @property integer id
 * @property string key
 * @property string title
 * @property text description
 * @property string language
 * @property text code
 * @property integer timeout
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
 * ),
 * @OA\Schema(
 *   schema="scripts",
 *   allOf={@OA\Schema(ref="#/components/schemas/scriptsEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * ),
 * 
 * @OA\Schema(
 *   schema="scriptsPreview",
 *   @OA\Property(property="status", type="string"),
 * )
 *
 */
class Script extends Model
{
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'timeout' => 'integer',
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
        $unique = Rule::unique('scripts')->ignore($existing);

        return [
            'key' => 'unique:scripts,key',
            'title' => ['required', 'string', $unique],
            'language' => [
                'required',
                Rule::in(static::scriptFormatValues())
            ],
            'description' => 'required',
            'run_as_user_id' => 'required',
            'timeout' => 'integer|min:0|max:65535',
        ];
    }

    /**
     * Executes a script given a configuration and data input.
     *
     * @param array $data
     * @param array $config
     */
    public function runScript(array $data, array $config)
    {
        $runner = new ScriptRunner($this->language);
        $user = User::find($this->run_as_user_id);
        if (!$user) {
            throw new \RuntimeException("A user is required to run scripts");
        }
        return $runner->run($this->code, $data, $config, $this->timeout, $user);
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
        # return the default admin user
        return User::where('is_administrator', true)->firstOrFail();
    }

    /**
     * Get the associated category
     */
    public function category()
    {
        return $this->belongsTo(ScriptCategory::class, 'script_category_id');
    }
}
