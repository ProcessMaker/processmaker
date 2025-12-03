<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\ScriptExecutor;

class ScriptMicroservicesHelper
{
    public static function getAccessToken()
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

        return $response->json()['access_token'];
    }

    public static function createCustomExecutor(ScriptExecutor $scriptExecutor)
    {
        Log::info('Creating custom script executor...');
        $url = config('script-runner-microservice.base_url') . '/custom/' . config('script-runner-microservice.instance_uuid') . '/scripts';
        Log::debug('Uri: ' . $url);
        $payload = [
            'id' => $scriptExecutor->uuid,
            'name' => $scriptExecutor->title,
            'language' => strtolower($scriptExecutor->language),
            'version' => config('script-runner-microservice.version'),
            'config' => $scriptExecutor->config,
            'callback' => config('script-runner-microservice.callback'),
        ];
        Log::debug('Payload: ', $payload);
        $response = Http::withToken(self::getAccessToken())
            ->post($url, $payload);

        return $response->json();
    }
}
