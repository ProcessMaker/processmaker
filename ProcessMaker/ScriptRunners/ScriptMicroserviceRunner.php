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
use stdClass;

class ScriptMicroserviceRunner
{
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

        $response = Http::asForm()->post(config('script-runner-microservice.keycloak.base_url') ?? '', [
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

    public function run($code, array $data, array $config, $timeout, $user, $sync, $metadata)
    {
        Log::debug('Language: ' . $this->language);
        Log::debug('Sync: ' . $sync);
        Log::debug('Metadata: ' . print_r($metadata, true));

        $scriptRunner = $this->getScriptRunner();

        if (!$scriptRunner) {
            throw new ConfigurationException('No exists script executor for this language: ' . $this->language);
        }
        $metadata = array_merge($this->getMetadata($user), $metadata);
        $environmentVariables = $this->getEnvironmentVariables($user);

        $payload = [
            'version' => config('script-runner-microservice.version') ?? $this->getProcessMakerVersion(),
            'language' => $scriptRunner['language'],
            'metadata'=> $metadata,
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

        // Set a theoretical maximum timeout of 1 day (86400 seconds)
        // since the laravel client must have a timeout set.
        // The actual script timeout will be handled by the microservice.
        $clientTimeout = 86400;

        $response = Http::timeout($clientTimeout)
            ->withToken($this->getAccessToken())
            ->post(config('script-runner-microservice.base_url') . '/requests/create', $payload);

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
