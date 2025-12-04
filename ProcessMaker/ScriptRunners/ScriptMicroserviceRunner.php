<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Helpers\ScriptMicroservicesHelper;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use stdClass;

class ScriptMicroserviceRunner
{
    private string $tokenId = '';

    private string $language;

    public function __construct(protected Script $script)
    {
        $this->language = strtolower($script->language ?? $script->scriptExecutor->language);
    }

    public function run($code, array $data, array $config, $timeout, $user, $sync, $metadata)
    {
        Log::debug('Language: ' . $this->language);
        Log::debug('Sync: ' . $sync);
        Log::debug('Metadata: ' . print_r($metadata, true));
        Log::debug('Script type: ' . $this->script->scriptExecutor->type?->value);

        $scriptRunner = ScriptMicroservicesHelper::getScriptRunner(
            $this->language,
            $this->script->scriptExecutor->uuid,
            $this->script->scriptExecutor->type === ScriptExecutorType::Custom
        );

        if (!$scriptRunner) {
            throw new ConfigurationException('No exists script executor for this language: ' . $this->language);
        }
        $metadata = array_merge($this->getMetadata($user), $metadata);
        $environmentVariables = $this->getEnvironmentVariables($user);

        $payload = [
            'version' => config('script-runner-microservice.version') ?? $this->getProcessMakerVersion(),
            'language' => $scriptRunner['language'],
            'metadata' => $metadata,
            'data' => !empty($data) ? $this->sanitizeCss($data) : new stdClass(),
            'config' => !empty($config) ? $config : new stdClass(),
            'script' => base64_encode(str_replace("'", '&#39;', $code)),
            'secrets' => $environmentVariables,
            'callback' => config('script-runner-microservice.callback'),
            'callback_secure' => true,
            'callback_token' => $environmentVariables['API_TOKEN'],
            'debug' => true,
            'timeout' => $timeout,
            'sync' => $sync,
        ];

        Log::debug('Payload: ' . print_r($payload, true));

        $response = ScriptMicroservicesHelper::sendScriptPayload($payload);

        $response->throw();

        $result = $response->json();

        if ($sync) {
            ErrorHandling::convertResponseToException($result);
        }

        return $result;
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
            'executor_uuid' => $this->script->scriptExecutor->uuid,
            'executor_type' => $this->script->scriptExecutor->type?->value,
            'instance' => config('app.url'),
            'instance_uuid' => config('script-runner-microservice.instance_uuid'),
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
