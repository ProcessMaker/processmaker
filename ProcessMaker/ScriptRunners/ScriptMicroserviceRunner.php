<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use stdClass;

class ScriptMicroserviceRunner
{
    private string $tokenId = '';

    public function __construct(protected Script $script)
    {
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

    public function getScriptRunner()
    {
        $response = Cache::remember('script-runner-microservice.script-languages', now()->addDay(), function () {
            return Http::withToken($this->getAccessToken())
                ->get(config('script-runner-microservice.base_url') . '/scripts')->collect();
        });

        return $response->filter(function ($item) {
            return $item['language'] == $this->script->language;
        })->first();
    }

    public function run($code, array $data, array $config, $timeout, $user)
    {
        Log::debug('Language: ' . $this->script->language);

        $scriptRunner = $this->getScriptRunner();

        if (!$scriptRunner) {
            throw new ConfigurationException('No script executor exists for this language: ' . $this->script->language);
        }

        $payload = [
            'version' => 'v4.11.2', //$this->getProcessMakerVersion(),
            'language' => $scriptRunner['language'],
            'metadata'=> [
                'nonce' => $this->script->nonce,
                'script_id' => $this->script->id,
                'instance' => config('app.url'),
                'user_id' => $user->id,
                'user_email' => $user->email,
            ],
            'data' => !empty($data) ? $data : new stdClass(),
            'config' => !empty($config) ? $config : new stdClass(),
            'script' => base64_encode(str_replace("'", '&#39;', $code)),
            'secrets' => $this->getEnvironmentVariables(),
            'callback' => config('script-runner-microservice.callback'),
            'debug' => true,
        ];

        Log::debug(print_r($payload, true));

        return Http::withToken($this->getAccessToken())
            ->post(config('script-runner-microservice.base_url') . '/requests/create', $payload)
            ->json();
    }

    private function getEnvironmentVariables()
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
}
