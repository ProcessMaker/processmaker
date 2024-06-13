<?php

namespace ProcessMaker\ScriptRunners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptDockerBindingFilesTrait;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use ProcessMaker\Models\ScriptDockerNayraTrait;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use RuntimeException;

abstract class Base
{
    use ScriptDockerCopyingFilesTrait;
    use ScriptDockerBindingFilesTrait;
    use ScriptDockerNayraTrait;

    const NAYRA_LANG = 'php-nayra';

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
     * @var \ProcessMaker\Models\ScriptExecutor
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
        $isNayra = $this->scriptExecutor->language === self::NAYRA_LANG;

        // Prepare the docker parameters
        $environmentVariables = $this->getEnvironmentVariables(!$isNayra);
        if (!getenv('HOME')) {
            putenv('HOME=' . base_path());
        }

        // Create tokens for the SDK if a user is set
        $token = null;
        if ($user) {
            $expires = Carbon::now()->addWeek();
            $accessToken = Cache::remember('script-runner-' . $user->id, $expires, function () use ($user) {
                $user->removeOldRunScriptTokens();
                $token = new GenerateAccessToken($user);
                return $token->getToken();
            });
            $environmentVariables[] = 'API_TOKEN=' . (!$isNayra ? escapeshellarg($accessToken) : $accessToken);
            $environmentVariables[] = 'API_HOST=' . config('app.docker_host_url') . '/api/1.0';
            $environmentVariables[] = 'APP_URL=' . config('app.docker_host_url');
            $environmentVariables[] = 'API_SSL_VERIFY=' . (config('app.api_ssl_verify') ? '1' : '0');
        }

        // Nayra Executor
        if ($isNayra) {
            $response = $this->handleNayraDocker($code, $data, $config, $timeout, $environmentVariables);
            return json_decode($response, true);
        }

        if ($environmentVariables) {
            $parameters = '-e ' . implode(' -e ', $environmentVariables);
        } else {
            $parameters = '';
        }

        // Set docker shared memory size
        $parameters .= ' --shm-size=' . env('DOCKER_SHARED_MEMORY', '256m');

        // Add any custom parameters specified in the config file
        $parameters .= ' ' . config('app.processmaker_scripts_docker_params');

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
        if (!isset($dockerConfig['image'])) {
            $dockerConfig['image'] = $this->scriptExecutor->dockerImageName();
        }

        // Execute docker
        $executeMethod = config('app.processmaker_scripts_docker_mode') === 'binding'
            ? 'executeBinding' : 'executeCopying';
        Log::debug('Executing docker ' . $this->getRunId() . ':', [
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

        Log::info("Docker returned {$this->getRunId()}", [
            'response' => [
                'responseCode' => $returnCode,
                'line' => $response['line'] ?? '',
                'stdOutput' => substr(json_encode($stdOutput), 0, 500) . '...',
                'outputs' => substr(json_encode($output), 0, 500) . '...',
            ],
        ]);

        if ($returnCode || $stdOutput) {
            // Has an error code
            throw new RuntimeException("(Code: {$returnCode})" . implode("\n", $stdOutput));
        }

        // Success
        return ['output' => json_decode($output, true)];
    }

    /**
     * Get the environment variables.
     *
     * @param bool $useEscape
     * @return array
     */
    private function getEnvironmentVariables($useEscape = true)
    {
        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function ($variables) use (&$variablesParameter, $useEscape) {
            foreach ($variables as $variable) {
                if ($useEscape) {
                    $variablesParameter[] = escapeshellarg($variable['name']) . '=' . escapeshellarg($variable['value']);
                } else {
                    $variablesParameter[] = $variable['name'] . '=' . $variable['value'];
                }
            }
        });

        // Add the url to the host
        $variablesParameter[] = 'HOST_URL=' . escapeshellarg(config('app.docker_host_url'));

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
        return $this->tokenId ? '#' . $this->tokenId : '';
    }
}
