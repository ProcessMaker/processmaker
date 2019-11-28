<?php

namespace ProcessMaker\ScriptRunners;

use Log;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptDockerBindingFilesTrait;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use ProcessMaker\Models\User;
use RuntimeException;
use ProcessMaker\GenerateAccessToken;

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
     * Set the user to run this script as
     *
     * @var \ProcessMaker\Models\User $user
     */
    private $user;

    /**
     * Run a script code.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     * @param integer $timeout
     * @param \ProcessMaker\Models\User $user
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config, $timeout, ?User $user)
    {
        // Prepare the docker parameters
        $environmentVariables = $this->getEnvironmentVariables();
    
        // Create tokens for the SDK if a user is set
        $token = null;
        if ($user) {
            $token = new GenerateAccessToken($user);
            $environmentVariables[] = 'API_TOKEN=' . $token->getToken();
            $environmentVariables[] = 'API_HOST=' . config('app.url') . '/api/1.0';
            $environmentVariables[] = 'API_SSL_VERIFY=' . (config('app.api_ssl_verify') ? '1' : '0');
        }

        if ($environmentVariables) {
            $parameters = '-e ' . implode(' -e ', $environmentVariables);
        } else {
            $parameters = '';
        }
        $dockerConfig = $this->config($code, [
            'timeout' => $timeout,
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
        $executeMethod = config('app.processmaker_scripts_docker_mode') === 'binding'
            ? 'executeBinding' : 'executeCopying';
        Log::debug('Executing docker', [
            'executeMethod' => $executeMethod,
        ]);
        $response = $this->$executeMethod($dockerConfig);

        // Delete the token we created for this run
        if ($token) {
            $token->delete();
        }

        // Process the output
        $returnCode = $response['returnCode'];
        $stdOutput = $response['output'];
        $output = $response['outputs']['response'];
        \Log::debug("Docker returned: " . json_encode($response));
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
