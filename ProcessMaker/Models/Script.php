<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Traits\SerializeToIso8601;

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
 *
 * @OA\Schema(
 *   schema="scriptsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="language", type="string"),
 *   @OA\Property(property="code", type="string"),
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
    use ScriptDockerCopyingFilesTrait;
    use ScriptDockerBindingFilesTrait;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    private static $scriptFormats = [
        'application/x-php' => 'php',
        'application/x-lua' => 'lua',
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
            'language' => 'required|in:php,lua'
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
        $code = $this->code;
        $language = $this->language;

        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function ($variables) use (&$variablesParameter) {
            foreach ($variables as $variable) {
                $variablesParameter[] = $variable['name'] . '=' . $variable['value'];
            }
        });

        if ($variablesParameter) {
            $variablesParameter = "-e " . implode(" -e ", $variablesParameter);
        } else {
            $variablesParameter = '';
        }

        // So we have the files, let's execute the docker container
        switch (strtolower($language)) {
            case 'php':
                $config = [
                    'image' => 'processmaker/executor:php',
                    'command' => 'php /opt/executor/bootstrap.php',
                    'parameters' => $variablesParameter,
                    'inputs' => [
                        '/opt/executor/data.json' => json_encode($data),
                        '/opt/executor/config.json' => json_encode($config),
                        '/opt/executor/script.php' => $code
                    ],
                    'outputs' => [
                        'response' => '/opt/executor/output.json'
                    ]
                ];
                break;
            case 'lua':
                $config = [
                    'image' => 'processmaker/executor:lua',
                    'command' => 'lua5.3 /opt/executor/bootstrap.lua',
                    'parameters' => $variablesParameter,
                    'inputs' => [
                        '/opt/executor/data.json' => json_encode($data),
                        '/opt/executor/config.json' => json_encode($config),
                        '/opt/executor/script.lua' => $code
                    ],
                    'outputs' => [
                        'response' => '/opt/executor/output.json'
                    ]
                ];
                break;
            default:
                throw new ScriptLanguageNotSupported($language);
        }

        $executeMethod = config('app.bpm_scripts_docker_mode')==='binding'
            ? 'executeBinding' : 'executeCopying';
        $response = $this->$executeMethod($config);
        $returnCode = $response['returnCode'];
        $stdOutput = $response['output'];
        $output = $response['outputs']['response'];
        if ($returnCode || $stdOutput) {
            // Has an error code
            return [
                'output' => implode($stdOutput, "\n")
            ];
        } else {
            // Success
            $response = json_decode($output, true);
            return [
                'output' => $response
            ];
        }
    }

    /**
     * Get the language from a script format string.
     *
     * @param string $format
     *
     * @return string
     */
    public static function scriptFormat2Language($format)
    {
        return static::$scriptFormats[$format];
    }
    
    /**
     * Get the associated versions
     */
    public function versions()
    {
        return $this->hasMany(ScriptVersion::class);
    }
}
