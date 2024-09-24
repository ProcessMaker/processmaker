<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Exception\ConfigurationException;

class RestScriptRunner
{
    public function __construct(protected string $language)
    {
    }

    public function getAccessToken()
    {
        if (Cache::has('keycloak.access_token')) {
            return Cache::get('keycloak.access_token');
        }

        $response = Http::asForm()->post(config('rest-script-runner.keycloak.base_url'), [
            'grant_type' => 'password',
            'client_id' => config('rest-script-runner.keycloak.client_id'),
            'client_secret' => config('rest-script-runner.keycloak.client_secret'),
            'username' => config('rest-script-runner.keycloak.username'),
            'password' => config('rest-script-runner.keycloak.password'),
        ]);

        if ($response->successful()) {
            Cache::put('keycloak.access_token', $response->json()['access_token'], $response->json()['expires_in'] - 60);
        }

        return Cache::get('keycloak.access_token');
    }

    public function getScriptRunner()
    {
        $response = Http::asForm()
            ->withToken($this->getAccessToken())
            ->get(config('rest-script-runner.microservice.base_url') . '/scripts')->collect();

        return $response->filter(function ($item) {
            return $item['language'] == $this->language;
        })->first();
    }

    public function run($code, array $data, array $config, $timeout, $user)
    {
        \Log::info('Language: ' . $this->language);

        $scriptRunner = $this->getScriptRunner();

        if (!$scriptRunner) {
            throw new ConfigurationException('No script executor exists for this language: ' . $this->language);
        }
        $payload = [
            'version' => $scriptRunner['version'],
            'language' => $scriptRunner['language'],
            'metadata'=> [
                'script_id' => 23455,
                'instance' => 'test-instance',
            ],
            'data' => [
                'name' => 'enrique',
                'email' => 'enrique@processmaker.com',
            ],
            'config' => [
                'status' => 'ok',
                'geolocation' => 'america/la_paz',
            ],
            'script' =>$code,
            //"script" => "<?php\nreturn [\"Hello\"=>\"World!!!\"];",
            'secrets' => [
                'API_HOST' => 'http://localhost',
                'API_TOKEN' => '123456789',
                'OTEL_PHP_AUTOLOAD_ENABLED' => true,
                'OTEL_TRACES_EXPORTER' => 'otlp',
                'OTEL_EXPORTER_OTLP_ENDPOINT' => 'api.honeycomb.io:443',
                'OTEL_EXPORTER_OTLP_TRACES_HEADERS' =>'x-honeycomb-team=LKxDrfqNmFrJnMK71cJwuD',
            ],
            'callback' => 'https://stm-app.free.beeceptor.com',
            'debug' => true,
        ];

        \Log::info(print_r($payload, true));

        return Http::withToken($this->getAccessToken())
            ->post(config('rest-script-runner.microservice.base_url') . '/requests/create', $payload)
            ->json();
    }
}
