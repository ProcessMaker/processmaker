<?php

namespace ProcessMaker\Mail;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MicrosoftGraphTokenProvider
{
    private const TOKEN_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';

    private const DEFAULT_SCOPE = 'https://graph.microsoft.com/.default';

    public function __construct(
        private array $config,
        private int|string $serverIndex = 0,
        private ?Client $httpClient = null
    ) {
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'microsoft_graph_access_token_' . ($this->serverIndex ?: 'default');

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            return $this->requestAccessToken();
        });
    }

    private function requestAccessToken(): string
    {
        $tenantId = $this->config['tenant_id'] ?? null;
        $clientId = $this->config['key'] ?? null;
        $clientSecret = $this->config['secret'] ?? null;

        if (!$tenantId || !$clientId || !$clientSecret) {
            throw new RuntimeException('Microsoft Graph credentials are not configured.');
        }

        $client = $this->httpClient ?? new Client();
        $response = $client->post(sprintf(self::TOKEN_URL, $tenantId), [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => self::DEFAULT_SCOPE,
                'grant_type' => 'client_credentials',
            ],
            'http_errors' => false,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() >= 400) {
            $message = $body['error_description'] ?? $body['error']['message'] ?? 'Unknown error';

            throw new RuntimeException('Failed to get Microsoft Graph access token: ' . $message);
        }

        return $body['access_token'];
    }
}
