<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\EnvironmentVariable;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents an Eloquent model of a Script
 *
 * @package ProcessMaker\Model
 *
 * @property string uuid
 * @property string title
 * @property text description
 * @property string language 
 * @property text code 
 *
 */
class Script extends Model
{
    use HasBinaryUuid;
    
    const LANGUAGE_PHP = 'PHP';
    const LANGUAGE_LUA = 'LUA';

    protected $guarded = [
        'uuid',
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
        $rules = [
            'title' => 'required|unique:scripts,title',
            'language' => 'required|in:' . self::LANGUAGE_LUA . ',' . self::LANGUAGE_PHP
        ];
        if ($existing) {
            // ignore the unique rule for this id
            $rules['title'] .= ',' . $existing->uuid . ',uuid';
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
        $datafname = tempnam("/home/vagrant", "data.json");
        chmod($datafname, 0660);
        $tempData = fopen($datafname, 'w');
        fwrite($tempData, json_encode($data));

        fclose($tempData);
        $configfname = tempnam("/home/vagrant", "config.json");
        chmod($configfname, 0660);

        $tempData = fopen($configfname, 'w');
        fwrite($tempData, json_encode($config));
        fclose($tempData);
        $scriptfname = tempnam("/home/vagrant", "script");
        chmod($scriptfname, 0660);

        $tempData = fopen($scriptfname, 'w');
        fwrite($tempData, $code);
        fclose($tempData);
        $outputfname = tempnam("/home/vagrant", "output.json");
        chmod($outputfname, 0660);

        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function($variables) use(&$variablesParameter) {
            foreach($variables as $variable) {
                $variablesParameter[] = $variable['name'] . '=' . $variable['value'];
            }
        });

        if($variablesParameter) {
            $variablesParameter = "-e " . implode(" -e ", $variablesParameter);
        } else {
            $variablesParameter = '';
        }

        // So we have the files, let's execute the docker container
        switch($language) {
            case 'php':
                $cmd = "/usr/bin/docker run " . $variablesParameter . " -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.php -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:php php /opt/executor/bootstrap.php 2>&1";
                break;
            case 'lua':
                $cmd = "/usr/bin/docker run " . $variablesParameter . " -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.lua -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:lua lua5.3 /opt/executor/bootstrap.lua 2>&1";
                break;
        }

        $response = exec($cmd, $output, $returnCode);
        if($returnCode) {
            // Non-zero, there is an error!
            unlink($datafname);
            unlink($configfname);
            unlink($scriptfname);
            unlink($outputfname);
            return [
                'output' => implode($output, "\n")
            ];
        } else {
            // Success, let's format and output
            // Grab output
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
}
