<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ScriptException;
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

    private $tenantChecked = false;

    private function client()
    {
        if (!$this->client) {
            $this->client = Http::withOptions([
                'verify' => !App::environment('local'),
            ])->baseUrl(config('script-runner-microservice.base_url'))
                ->withToken($this->getAccessToken())
                ->accept('application/json')
                ->contentType('application/json')
                ->throw();
        }

        return $this->client;
    }

    private function checkTenant(): void
    {
        if ($this->tenantChecked) {
            return;
        }

        $instanceUuid = $this->getInstanceUuid();
        $url = '/tenants/' . $instanceUuid;
        $client = $this->client();

        try {
            $response = $client->get($url);
            // Tenant exists, we're good
        } catch (RequestException $e) {
            // If we get a 404, create the tenant
            if ($e->response && $e->response->status() === 404) {
                Log::debug('Tenant not found, creating.', ['instanceUuid' => $instanceUuid]);
                $client->post('/tenants', [
                    'name' => config('app.instance'),
                    'id' => $instanceUuid,
                ]);
            } else {
                // Re-throw if it's not a 404
                Log::error('Error checking tenant', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        $this->tenantChecked = true;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function createCustomExecutor(ScriptExecutor $scriptExecutor)
    {
        $url = '/custom/' . $this->getInstanceUuid() . '/scripts';
        Log::debug('Creating custom script executor.', ['url' => $url]);
        $payload = [
            'id' => $scriptExecutor->uuid,
            'name' => $scriptExecutor->title,
            'description' => $scriptExecutor->description,
            'language' => strtolower($scriptExecutor->language),
            'version' => config('script-runner-microservice.version'),
            'config' => $scriptExecutor->config,
        ];
        Log::debug('Payload: ', $payload);

        $this->checkTenant();
        $response = $this->client()->post($url, $payload);

        $jsonResponse = $response->json();
        Log::debug('Response', ['response' => $jsonResponse]);

        return $jsonResponse;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function updateCustomExecutor(ScriptExecutor $scriptExecutor)
    {
        $this->checkTenant();
        $url = '/custom/scripts/' . $scriptExecutor->uuid;
        Log::debug('Updating custom script executor.', ['url' => $url]);
        $payload = [
            'name' => $scriptExecutor->title,
            'description' => $scriptExecutor->description,
            'language' => strtolower($scriptExecutor->language),
            'version' => config('script-runner-microservice.version'),
            'config' => $scriptExecutor->config,
        ];
        Log::debug('Payload: ', $payload);

        try {
            $response = $this->client()
                ->put($url, $payload);

            $jsonResponse = $response->json();
            Log::debug('Response', ['response' => $jsonResponse]);

            return $jsonResponse;
        } catch (RequestException $e) {
            // If we get a 404, create the executor instead
            if ($e->response && $e->response->status() === 404) {
                Log::debug('Executor not found (404), creating instead...');

                return $this->createCustomExecutor($scriptExecutor);
            } else {
                // Re-throw if it's not a 404
                throw $e;
            }
        }
    }

    /**
     * @throws ConnectionException
     */
    public function deleteCustomExecutor($scriptExecutorUUID)
    {
        $url = '/custom/scripts/' . $scriptExecutorUUID;
        Log::debug('Deleting custom script executor.', ['url' => $url]);

        $response = $this->client()->delete($url);

        $jsonResponse = $response->json();
        Log::debug('Response', ['response' => $jsonResponse]);

        return $jsonResponse;
    }

    /**
     * @throws ConnectionException
     */
    public function getAccessToken(): string
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

        $responseJson = $response->json();

        if ($response->successful() && isset($responseJson['access_token'])) {
            // store with a small buffer before expiration
            Cache::put('keycloak.access_token', $responseJson['access_token'], max(60, ($responseJson['expires_in'] ?? 1800) - 60));

            return $responseJson['access_token'];
        }

        Log::error('Failed to obtain access token', ['status' => $response->status(), 'body' => $responseJson]);
        throw new \RuntimeException('Unable to obtain access token from Keycloak');
    }

    /**
     * @throws ConnectionException
     */
    public function getScriptRunner(string $language, string $executorUuid, bool $custom = false): array|null
    {
        $cacheKey = $custom
            ? ('script-runner-microservice.custom-script-runner.' . $executorUuid)
            : ('script-runner-microservice.script-runner.' . $language);

        if (Cache::has($cacheKey)) {
            Log::debug('Cache hit for script runner', ['cacheKey' => $cacheKey]);
            return Cache::get($cacheKey);
        }

        $uri = !$custom ? '/scripts' : '/custom/' . $this->getInstanceUuid() . '/scripts';

        $result = $this->client()
            ->get($uri)
            ->collect()
            ->filter(function ($item) use ($language, $executorUuid, $custom) {
                if (!$custom) {
                    return isset($item['language']) && $item['language'] === $language;
                }

                return isset($item['language'], $item['id']) && $item['language'] === $language && $item['id'] === $executorUuid;
            })->first();

        if (!empty($result)) Cache::put($cacheKey, $result, now()->addHour());

        return $result;
    }

    /**
     * @throws ConnectionException
     */
    public function sendScriptPayload($payload)
    {
        $uri = '/requests/create';
        // Set a theoretical maximum timeout of 1 day (86400 seconds)
        // since the laravel client must have a timeout set.
        // The actual script timeout will be handled by the microservice.
        $clientTimeout = 86400;

        return $this->client()->timeout($clientTimeout)
            ->post($uri, $payload);
    }

    public function handle(Request $request): void
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
        $output = $response;
        if (($response['status'] ?? '') === 'success') {
            $output = ['output' => $response['output']];
        } elseif ($response['status'] === 'error') {
            $output = [
                'exception' => $response['exception'] ?? ScriptException::class,
                'message' => $response['error'],
            ];
        }

        return $output;
    }

    public function getInstanceUuid(): string
    {
        return Uuid::uuid5(
            Uuid::fromString('817d1d4c-e05c-4244-bf36-445e117d431a'),
            config('app.url')
        )->toString();
    }
}
