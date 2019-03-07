<?php

namespace ProcessMaker\ScriptRunners;

use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptDockerBindingFilesTrait;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use RuntimeException;

abstract class Base
{
    use ScriptDockerCopyingFilesTrait;
    use ScriptDockerBindingFilesTrait;

    /**
     * Prepare the docker configuration.
     *
     * @param string $code
     * @param array $dockerConfig
     *
     * @return array
     */
    abstract public function config($code, array $dockerConfig);

    /**
     * Run a script code.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config)
    {
        // Prepate the docker parameters
        $environmentVariables = $this->getEnvironmentVariables();

        if ($environmentVariables) {
            $parameters = '-e ' . implode(' -e ', $environmentVariables);
        } else {
            $parameters = '';
        }
        $dockerConfig = $this->config($code, [
            'parameters' => $parameters,
            'inputs' => [
                '/opt/executor/data.json' => json_encode($data),
                '/opt/executor/config.json' => json_encode($config),
            ],
            'outputs' => [
                'response' => '/opt/executor/output.json'
            ]
        ]);

        // Execute docker
        $executeMethod = config('app.bpm_scripts_docker_mode') === 'binding'
            ? 'executeBinding' : 'executeCopying';
        $response = $this->$executeMethod($dockerConfig);

        // Process the output
        $returnCode = $response['returnCode'];
        $stdOutput = $response['output'];
        $output = $response['outputs']['response'];
        if ($returnCode || $stdOutput) {
            // Has an error code
            throw new RuntimeException(implode("\n", $stdOutput));
        } else {
            // Success
            $response = json_decode($output, true);
            return [
                'output' => $response
            ];
        }
    }

    /**
     * Get the environment variables.
     *
     * @return array
     */
    private function getEnvironmentVariables()
    {
        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function ($variables) use (&$variablesParameter) {
            foreach ($variables as $variable) {
                $variablesParameter[] = escapeshellarg($variable['name']) . '=' . escapeshellarg($variable['value']);
            }
        });

        // Add the url to the host
        $variablesParameter[] = 'HOST_URL=' . escapeshellarg(config('app.docker_host_url'));

        return $variablesParameter;
    }
}
