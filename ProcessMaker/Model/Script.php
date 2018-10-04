<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Model\EnvironmentVariable;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an Eloquent model of a Script
 *
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property string title
 * @property string description
 * @property int process_id
 * @property string type
 * @property text webbot
 * @property array param
 *
 */
class Script extends Model
{
    use ValidatingTrait;
    use Uuid;

    protected $table = 'scripts';
    protected $injectUniqueIdentifier = true;

    const SCRIPT_TYPE = 'SCRIPT';

    protected $fillable = [
        'uid',
        'title',
        'description',
        'process_id',
        'language',
        'code'
    ];

    protected $attributes = [
        'uid' => null,
        'title' => '',
        'description' => '',
        'process_id' => '',
        'language' => '',
        'code' => ''
    ];
    protected $casts = [
        'uid' => 'string',
        'title' => 'string',
        'description' => 'string',
        'process_id' => 'int',
        'language' => 'string',
        'code' => 'string'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'title' => 'required|unique:scripts,title',
        'process_id' => 'exists:processes,id',
        'language' => 'required'
    ];

    protected $validationMessages = [
        'title.unique' => 'A script with the same name already exists in this process.',
        'process_id.exists' => 'Process not found.'
    ];

    /**
     * Validating fields unique
     *
     * @param $parameters
     * @param $field
     *
     * @return \Illuminate\Validation\Rules\Unique
     */
    protected function prepareUniqueRule($parameters, $field)
    {
        if ($field === 'title') {
            return Rule::unique('scripts')->where(function ($query) {
                $query->where('process_id', $this->process_id);
            })->ignore($this->id);
        }
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Get the process we belong to.
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
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
