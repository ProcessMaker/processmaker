<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
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
 *   @OA\Schema(
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
        $rules = [
            'key' => 'unique:scripts,key',
            'title' => 'required|unique:scripts,title',
            'language' => 'required|in:php,lua'
        ];
        if ($existing) {
            // ignore the unique rule for this id
            $rules['title'] = [
                'required',
                'string',
                Rule::unique('scripts')->ignore($existing->id, 'id')
            ];
        }
        return $rules;
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
        // Create the temporary files to feed into our docker container
        $datafname = tempnam(config('app.bpm_scripts_home'), "data.json");
        chmod($datafname, 0660);
        $tempData = fopen($datafname, 'w');
        fwrite($tempData, json_encode($data));

        fclose($tempData);
        $configfname = tempnam(config('app.bpm_scripts_home'), "config.json");
        chmod($configfname, 0660);

        $tempData = fopen($configfname, 'w');
        fwrite($tempData, json_encode($config));
        fclose($tempData);
        $scriptfname = tempnam(config('app.bpm_scripts_home'), "script");
        chmod($scriptfname, 0660);

        $tempData = fopen($scriptfname, 'w');
        fwrite($tempData, $code);
        fclose($tempData);
        $outputfname = tempnam(config('app.bpm_scripts_home'), "output.json");
        chmod($outputfname, 0660);

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
                $cmd = config('app.bpm_scripts_docker') . " run " . $variablesParameter . " -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.php -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:php php /opt/executor/bootstrap.php 2>&1";
                break;
            case 'lua':
                $cmd = config('app.bpm_scripts_docker') . " run " . $variablesParameter . " -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.lua -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:lua lua5.3 /opt/executor/bootstrap.lua 2>&1";
                break;
            default:
                throw new ScriptLanguageNotSupported($language);
        }

        $response = exec($cmd, $output, $returnCode);
        if ($returnCode) {
            // Has an error code
            unlink($datafname);
            unlink($configfname);
            unlink($scriptfname);
            unlink($outputfname);
            return [
                'output' => implode($output, "\n")
            ];
        } else {
            // Success
            $output = json_decode(file_get_contents($outputfname), true);
            unlink($datafname);
            unlink($configfname);
            unlink($scriptfname);
            unlink($outputfname);
            return [
                'output' => $output
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
}
