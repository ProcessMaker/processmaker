<?php

namespace ProcessMaker\ScriptRunners;

use Log;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptDockerBindingFilesTrait;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use RuntimeException;

abstract class Base
{
    use ScriptDockerCopyingFilesTrait;
    use ScriptDockerBindingFilesTrait;

    private $tokenId = '';

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
     * @var \ProcessMaker\Models\User
     */
    private $user;

    /**
     * Set the script executor
     *
     * @var \ProcessMaker\Models\User
     */
    private $scriptExecutor;

    public function __construct(ScriptExecutor $scriptExecutor)
    {
        $this->scriptExecutor = $scriptExecutor;
    }

    /**
     * Run a script code.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     * @param int $timeout
     * @param \ProcessMaker\Models\User $user
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config, $timeout, ?User $user)
    {
        // Prepare the docker parameters
        $environmentVariables = $this->getEnvironmentVariables();
        if (! getenv('HOME')) {
            putenv('HOME='.base_path());
        }

        // Create tokens for the SDK if a user is set
        $token = null;
        if ($user) {
            $token = new GenerateAccessToken($user);
            $environmentVariables[] = 'API_TOKEN='.$token->getToken();
            $environmentVariables[] = 'API_HOST='.config('app.url').'/api/1.0';
            $environmentVariables[] = 'APP_URL='.config('app.url');
            $environmentVariables[] = 'API_SSL_VERIFY='.(config('app.api_ssl_verify') ? '1' : '0');
        }

        if ($environmentVariables) {
            $parameters = '-e '.implode(' -e ', $environmentVariables);
        } else {
            $parameters = '';
        }

        // Set docker shared memory size
        $parameters .= ' --shm-size='.env('DOCKER_SHARED_MEMORY', '256m');

        $dockerConfig = $this->config($code, [
            'timeout' => $timeout,
            'parameters' => $parameters,
            'inputs' => [
                '/opt/executor/data.json' => json_encode($data),
                '/opt/executor/config.json' => json_encode($config),
            ],
            'outputs' => [
                'response' => '/opt/executor/output.json',
            ],
        ]);

        // If the image is not specified, use the one set by the executor
        if (! isset($dockerConfig['image'])) {
            $dockerConfig['image'] = $this->scriptExecutor->dockerImageName();
        }

        // Execute docker
        $executeMethod = config('app.processmaker_scripts_docker_mode') === 'binding'
            ? 'executeBinding' : 'executeCopying';
        Log::debug('Executing docker '.$this->getRunId().':', [
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
        Log::debug('Docker returned '.$this->getRunId().': '.substr(json_encode($response), 0, 500));
        if ($returnCode || $stdOutput) {
            // Has an error code
            throw new RuntimeException("(Code: {$returnCode})".implode("\n", $stdOutput));
        } else {
            // Success
            $response = json_decode($output, true);

            return [
                'output' => $response,
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
                $variablesParameter[] = escapeshellarg($variable['name']).'='.escapeshellarg($variable['value']);
            }
        });

        // Add the url to the host
        $variablesParameter[] = 'HOST_URL='.escapeshellarg(config('app.docker_host_url'));

        return $variablesParameter;
    }

    /**
     * Set the tokenId of reference.
     *
     * @param string $tokenId
     *
     * @return void
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;
    }

    private function getRunId()
    {
        return $this->tokenId ? '#'.$this->tokenId : '';
    }
}
