<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use RuntimeException;
use stdClass;

class ScriptMicroserviceRunner
{
    private const DEFAULT_VERSION = '1.0.0';

    private string $tokenId = '';

    private string $language;

    public function __construct(protected Script $script)
    {
        $this->language = strtolower($script->language ?? $script->scriptExecutor->language);
    }

    public function getAccessToken()
    {
        if (Cache::has('keycloak.access_token')) {
            return Cache::get('keycloak.access_token');
        }

        $response = Http::asForm()->post(config('script-runner-microservice.keycloak.base_url'), [
            'grant_type' => 'password',
            'client_id' => config('script-runner-microservice.keycloak.client_id'),
            'client_secret' => config('script-runner-microservice.keycloak.client_secret'),
            'username' => config('script-runner-microservice.keycloak.username'),
            'password' => config('script-runner-microservice.keycloak.password'),
        ]);

        if ($response->successful()) {
            Cache::put('keycloak.access_token', $response->json()['access_token'], $response->json()['expires_in'] - 60);
        }

        return Cache::get('keycloak.access_token');
    }

    /**
     * Get script runner configuration for the current language
     *
     * @return array|null Script runner configuration
     */
    public function getScriptRunner()
    {
        $response = Cache::remember('script-runner-microservice.script-languages', now()->addDay(), function () {
            return Http::withToken($this->getAccessToken())
                ->get(config('script-runner-microservice.base_url') . '/scripts')->collect();
        });

        return $response->filter(function ($item) {
            return $item['language'] == $this->language;
        })->first();
    }

    /**
     * Run a script with the given parameters
     *
     * @param string $code The script code to execute
     * @param array $data Input data for the script
     * @param array $config Configuration options
     * @param int $timeout Maximum execution time
     * @param User $user User executing the script
     * @param bool $sync Whether to run synchronously
     * @param array $metadata Additional metadata
     * @return array Execution results
     * @throws \Exception
     */
    public function run($code, array $data, array $config, $timeout, $user, $sync, $metadata)
    {
        try {
            $this->validateScriptRunner();

            $payload = $this->buildPayload(
                $code,
                $data,
                $config,
                $timeout,
                $user,
                $sync,
                $metadata
            );

            return $this->sendRequest($payload, $sync);
        } catch (\Exception $e) {
            Log::error('Script runner error: ' . $e->getMessage(), [
                'language' => $this->language,
                'sync' => $sync,
                'metadata' => $metadata,
            ]);
            throw $e;
        }
    }

    /**
     * Validate that a script runner exists for the current language
     *
     * @throws ConfigurationException
     */
    private function validateScriptRunner(): void
    {
        $scriptRunner = $this->getScriptRunner();

        if (!$scriptRunner) {
            throw new ConfigurationException(
                "No script executor exists for language: {$this->language}"
            );
        }
    }

    /**
     * Build the payload for script execution
     *
     * @param string $code Script code
     * @param array $data Input data
     * @param array $config Configuration
     * @param int $timeout Timeout value
     * @param User $user User executing the script
     * @param bool $sync Synchronous execution flag
     * @param array $metadata Additional metadata
     * @return array Complete payload for the request
     */
    private function buildPayload($code, array $data, array $config, $timeout, $user, $sync, array $metadata): array
    {
        $scriptRunner = $this->getScriptRunner();
        $environmentVariables = $this->getEnvironmentVariables($user);
        $mergedMetadata = array_merge($this->getMetadata($user), $metadata);

        return [
            'version' => $this->getVersion(),
            'language' => $scriptRunner['language'],
            'metadata' => $mergedMetadata,
            'data' => $this->sanitizeData($data),
            'config' => $config ?: new stdClass(),
            'script' => $this->encodeScript($code),
            'secrets' => $environmentVariables,
            'callback' => config('script-runner-microservice.callback'),
            'callback_secure' => true,
            'callback_token' => $environmentVariables['API_TOKEN'],
            'debug' => true,
            'timeout' => $timeout,
            'sync' => $sync,
        ];
    }

    /**
     * Get the version for the script runner
     *
     * @return string Version number
     */
    private function getVersion(): string
    {
        return config('script-runner-microservice.version', $this->getProcessMakerVersion())
            ?: self::DEFAULT_VERSION;
    }

    /**
     * Sanitize input data
     *
     * @param array $data Input data
     * @return stdClass|array Sanitized data
     */
    private function sanitizeData(array $data): stdClass|array
    {
        return !empty($data) ? $this->sanitizeCss($data) : new stdClass();
    }

    /**
     * Encode script code for transmission
     *
     * @param string $code Script code
     * @return string Encoded script
     */
    private function encodeScript(string $code): string
    {
        return base64_encode(str_replace("'", '&#39;', $code));
    }

    /**
     * Send the execution request to the microservice
     *
     * @param array $payload Request payload
     * @param bool $sync Synchronous execution flag
     * @return array Execution results
     */
    private function sendRequest(array $payload, bool $sync)
    {
        $this->logDebugInfo($payload);

        $response = Http::withToken($this->getAccessToken())
            ->post($this->getEndpoint(), $payload);

        if ($response->failed()) {
            $this->handleFailedResponse($response);
        }

        $result = $response->json();

        if ($sync) {
            ErrorHandling::convertResponseToException($result);
        }

        return $result;
    }

    /**
     * Get the endpoint URL for the script runner
     *
     * @return string Endpoint URL
     */
    private function getEndpoint(): string
    {
        return rtrim(config('script-runner-microservice.base_url'), '/') . '/requests/create';
    }

    /**
     * Handle failed HTTP responses
     *
     * @param \Illuminate\Http\Client\Response $response
     * @throws RuntimeException
     */
    private function handleFailedResponse($response): void
    {
        $error = $response->json()['output']['error'] ?? $response->body();
        throw new RuntimeException("Script execution failed: {$error}");
    }

    /**
     * Log debug information about the script execution
     *
     * @param array $payload Request payload
     */
    private function logDebugInfo(array $payload): void
    {
        Log::debug('Script execution details', [
            'language' => $this->language,
            'sync' => $payload['sync'],
            'metadata' => $payload['metadata'],
            'payload' => $payload,
        ]);
    }

    private function getEnvironmentVariables(User $user)
    {
        $variablesParameter = [];
        EnvironmentVariable::chunk(50, function (Collection $variables) use (&$variablesParameter) {
            foreach ($variables as $variable) {
                // Fix variables that have spaces
                $variablesParameter[str_replace(' ', '_', $variable->name)] = $variable->value;
            }
        });

        // Add the url to the host
        $variablesParameter['HOST_URL'] = config('app.docker_host_url');

        // Create tokens for the SDK if a user is set
        $token = null;
        if ($user) {
            $accessToken = Cache::remember('script-runner-' . $user->id, now()->addWeek(), function () use ($user) {
                $user->removeOldRunScriptTokens();
                $token = new GenerateAccessToken($user);

                return $token->getToken();
            });
            $variablesParameter['API_TOKEN'] = $accessToken;
            $variablesParameter['API_HOST'] = config('app.docker_host_url') . '/api/1.0';
            $variablesParameter['APP_URL'] = config('app.docker_host_url');
            $variablesParameter['API_SSL_VERIFY'] = (config('app.api_ssl_verify') ? '1' : '0');
        }

        return $variablesParameter;
    }

    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;
    }

    public function getProcessMakerVersion()
    {
        return Cache::remember('script-runner-microservice.processmaker-version', now()->addDay(), function () {
            $composer_json_path = json_decode(file_get_contents(base_path() . '/composer.json'));

            return $composer_json_path->version;
        });
    }

    public function getMetadata($user)
    {
        return [
            'script_id' => $this->script->id,
            'instance' => config('app.url'),
            'user_id' => $user->id,
            'user_email' => $user->email,
        ];
    }

    public function sanitizeCss($data)
    {
        if ($this->language !== 'javascript-ssr') {
            return $data;
        }
        if (array_key_exists('css', $data)) {
            $data['css'] = false;
        }

        return $data;
    }
}
