<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptRunner;

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
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="language", type="string"),
 *   @OA\Property(property="code", type="string"),
 *   @OA\Property(property="teimout", type="integer"),
 * ),
 * @OA\Schema(
 *   schema="scripts",
 *   allOf={@OA\Schema(ref="#/components/schemas/scriptsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Script extends Model
{
    use SerializeToIso8601;

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
            'run_as_user_id' => 'required',
            'timeout' => 'integer|min:0|max:65535',
        ];
    }

    /**
     * Executes a script given a configuration and data input.
     *
     * @param array $data
     * @param array $config
     * @param \ProcessMaker\Models\User $user
     */
    public function runScript(array $data, array $config, User $asUser = null)
    {
        $runner = new ScriptRunner($this->language);
        return $runner->run($this->code, $data, $config, $this->timeout, $asUser);
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
}
