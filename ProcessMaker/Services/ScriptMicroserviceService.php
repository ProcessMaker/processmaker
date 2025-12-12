<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Ramsey\Uuid\Uuid;

class ScriptMicroserviceService
{
    private $client;

    public function __construct()
    {
        $this->client = Http::withOptions([
            'verify' => !App::environment('local'),
        ]);
    }

    public function createCustomExecutor(ScriptExecutor $scriptExecutor)
    {
        Log::info('Creating custom script executor...');
        $url = config('script-runner-microservice.base_url') . '/custom/' . $this->getInstanceUuid() . '/scripts';
        Log::debug('Uri: ' . $url);
        $payload = [
            'id' => $scriptExecutor->uuid,
            'name' => $scriptExecutor->title,
            'description' => $scriptExecutor->description,
            'language' => strtolower($scriptExecutor->language),
            'version' => config('script-runner-microservice.version'),
            'config' => $scriptExecutor->config,
        ];
        Log::debug('Payload: ', $payload);

        $response = $this->client->withToken($this->getAccessToken())
            ->post($url, $payload);

        return $response->json();
    }

    public function updateCustomExecutor(ScriptExecutor $scriptExecutor)
    {
        Log::info('Updating custom script executor...');
        $url = config('script-runner-microservice.base_url') . '/custom/scripts/' . $scriptExecutor->uuid;
        Log::debug('Uri: ' . $url);
        $payload = [
            'name' => $scriptExecutor->title,
            'description' => $scriptExecutor->description,
            'language' => strtolower($scriptExecutor->language),
            'version' => config('script-runner-microservice.version'),
            'config' => $scriptExecutor->config,
        ];
        Log::debug('Payload: ', $payload);

        $response = $this->client->withToken($this->getAccessToken())
            ->put($url, $payload);

        return $response->json();
    }

    public function deleteCustomExecutor($scriptExecutorUUID)
    {
        Log::info('Deleting custom script executor...');
        $url = config('script-runner-microservice.base_url') . '/custom/scripts/' . $scriptExecutorUUID;
        Log::debug('Uri: ' . $url);

        $response = $this->client->withToken($this->getAccessToken())
            ->delete($url);

        return $response->json();
    }

    public function getAccessToken()
    {
        if (Cache::has('keycloak.access_token')) {
            return Cache::get('keycloak.access_token');
        }

        $response = $this->client->asForm()->post(config('script-runner-microservice.keycloak.base_url') ?? '', [
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

    public function getScriptRunner($language, $executorUuid, $custom = false)
    {
        $uri = !$custom ?
            config('script-runner-microservice.base_url') . '/scripts' :
            config('script-runner-microservice.base_url') . '/custom/' . $this->getInstanceUuid() . '/scripts';

        if (!$custom && Cache::has('script-runner-microservice.script-runner')) {
            return Cache::get('script-runner-microservice.script-runner.' . $language);
        } elseif ($custom && Cache::has('script-runner-microservice.custom-script-runner.' . $executorUuid)) {
            return Cache::get('script-runner-microservice.custom-script-runner.' . $executorUuid);
        }

        $response = $this->client->withToken($this->getAccessToken())
            ->get($uri)->collect();

        $result = $response->filter(function ($item) use ($language, $executorUuid, $custom) {
            return !$custom ?
                $item['language'] == $language :
                $item['language'] === $language && $item['id'] === $executorUuid;
        })->first();

        if (!$custom) {
            Cache::put('script-runner-microservice.script-runner.' . $language, $result, now()->addHour());
        } else {
            Cache::put('script-runner-microservice.custom-script-runner.' . $executorUuid, $result, now()->addHour());
        }

        return $result;
    }

    public function sendScriptPayload($payload)
    {
        $uri = config('script-runner-microservice.base_url') . '/requests/create';
        // Set a theoretical maximum timeout of 1 day (86400 seconds)
        // since the laravel client must have a timeout set.
        // The actual script timeout will be handled by the microservice.
        $clientTimeout = 86400;

        return $this->client->timeout($clientTimeout)
            ->withToken($this->getAccessToken())
            ->post($uri, $payload);
    }

    public function handle(Request $request)
    {
        $response = $request->all();
        Log::debug('Response microservice executor: ' . print_r($response, true));
        // If the call is from preview
        if (!empty($response['metadata']['nonce'])) {
            $formattedResponse = $this->formatPreviewResponse($response);
            event(new ScriptResponseEvent(
                User::find($response['metadata']['current_user']),
                $formattedResponse['status'],
                $formattedResponse['output'],
                null,
                $response['metadata']['nonce']));
        }
        if (!empty($response['metadata']['script_task'])) {
            $script = Script::find($response['metadata']['script_task']['script_id']);
            $definitions = Definitions::find($response['metadata']['script_task']['definition_id']);
            $instance = ProcessRequest::find($response['metadata']['script_task']['instance_id']);
            $token = ProcessRequestToken::find($response['metadata']['script_task']['token_id']);
            if ($response['status'] === 'success') {
                CompleteActivity::dispatch($definitions, $instance, $token, $response['output'])->onQueue('bpmn');
            }
        }
    }

    /**
     * Format preview response data
     *
     * @param array $response
     * @return array{status: int, output: array}
     */
    private function formatPreviewResponse(array $response): array
    {
        // Simple status determination: success = 200, others = 500
        $status = $response['status'] === 'success' ? 200 : 500;

        return [
            'status' => $status,
            'output' => $this->formatPreviewOutput($response),
        ];
    }

    /**
     * Format preview output data
     *
     * @param array $response
     * @return array
     */
    private function formatPreviewOutput(array $response): array
    {
        // For successful responses, return just the output array
        if (($response['status'] ?? '') === 'success') {
            return [
                'output' => $response['output'],
            ];
        }

        // For error responses, include error details
        $output = $response;

        if (($response['status'] ?? '') === 'error') {
            $output['exception'] = $this->extractErrorDetails($response);
            $output['status'] = 'error';
        }

        return $output;
    }

    /**
     * Extract error details from response
     *
     * @param array $response
     * @return string
     */
    private function extractErrorDetails(array $response): string
    {
        if (isset($response['output']['error'])) {
            return $response['output']['error'];
        }

        if (isset($response['message'])) {
            return $response['message'];
        }

        return 'Unknown error occurred';
    }

    public function getInstanceUuid(): string
    {
        return Uuid::uuid5(
            Uuid::fromString('817d1d4c-e05c-4244-bf36-445e117d431a'),
            config('app.url')
        )->toString();
    }
}
