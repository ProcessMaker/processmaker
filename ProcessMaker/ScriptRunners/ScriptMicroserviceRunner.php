<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Models\Script;

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

        $response = Http::asForm()->post(config('script-runners.script-microservice.keycloak.base_url'), [
            'grant_type' => 'password',
            'client_id' => config('script-runners.script-microservice.keycloak.client_id'),
            'client_secret' => config('script-runners.script-microservice.keycloak.client_secret'),
            'username' => config('script-runners.script-microservice.keycloak.username'),
            'password' => config('script-runners.script-microservice.keycloak.password'),
        ]);

        if ($response->successful()) {
            Cache::put('keycloak.access_token', $response->json()['access_token'], $response->json()['expires_in'] - 60);
        }

        return Cache::get('keycloak.access_token');
    }

    public function getScriptRunner()
    {
        $response = Cache::remember('rest-scripts-runners', now()->addDay(), function () {
            return Http::withToken($this->getAccessToken())
                ->get(config('script-runners.script-microservice.base_url') . '/scripts')->collect();
        });

        return $response->filter(function ($item) {
            return $item['language'] == $this->script->language;
        })->first();
    }

    public function run($code, array $data, array $config, $timeout, $user)
    {
        \Log::info('Language: ' . $this->script->language);

        $scriptRunner = $this->getScriptRunner();

        if (!$scriptRunner) {
            throw new ConfigurationException('No script executor exists for this language: ' . $this->script->language);
        }
        $payload = [
            'version' => 'v4.11.2',
            'language' => $scriptRunner['language'],
            'metadata'=> [
                'script_id' => $this->script->id,
                'instance' => config('app.url'),
            ],
            'data' => [
                'name' => 'enrique',
                'email' => 'enrique@processmaker.com',
            ],
            'config' => [
                'status' => 'ok',
                'geolocation' => 'america/la_paz',
            ],
            'script' => base64_encode(str_replace("'", "&#39;", $code)),
            'secrets' => [
                'API_HOST' => 'http://localhost',
                'API_TOKEN' => '123456789',
                'OTEL_PHP_AUTOLOAD_ENABLED' => true,
                'OTEL_TRACES_EXPORTER' => 'otlp',
                'OTEL_EXPORTER_OTLP_ENDPOINT' => 'api.honeycomb.io:443',
                'OTEL_EXPORTER_OTLP_TRACES_HEADERS' =>'x-honeycomb-team=LKxDrfqNmFrJnMK71cJwuD',
            ],
            'callback' => config('script-runners.script-microservice.callback'),
            'debug' => true,
        ];

        \Log::info(print_r($payload, true));

        return Http::withToken($this->getAccessToken())
            ->post(config('script-runners.script-microservice.base_url') . '/requests/create', $payload)
            ->json();
    }

    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;
    }

}
