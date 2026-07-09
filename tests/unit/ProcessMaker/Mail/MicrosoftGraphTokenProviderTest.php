<?php

namespace Tests\Unit\ProcessMaker\Mail;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Mockery;
use ProcessMaker\Mail\MicrosoftGraphTokenProvider;
use RuntimeException;
use Tests\TestCase;

class MicrosoftGraphTokenProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetAccessTokenThrowsWhenCredentialsAreMissing()
    {
        $provider = new MicrosoftGraphTokenProvider([
            'tenant_id' => '',
            'key' => 'client-id',
            'secret' => 'client-secret',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Microsoft Graph credentials are not configured.');

        $provider->getAccessToken();
    }

    public function testGetAccessTokenRequestsTokenFromMicrosoft()
    {
        Cache::flush();

        $tokenResponseBody = Mockery::mock();
        $tokenResponseBody->shouldReceive('getContents')
            ->andReturn(json_encode(['access_token' => 'token-from-azure']));

        $tokenResponse = Mockery::mock();
        $tokenResponse->shouldReceive('getStatusCode')->andReturn(200);
        $tokenResponse->shouldReceive('getBody')->andReturn($tokenResponseBody);

        $guzzle = Mockery::mock(Client::class);
        $guzzle->shouldReceive('post')
            ->once()
            ->with(
                'https://login.microsoftonline.com/tenant-id-123/oauth2/v2.0/token',
                Mockery::on(function ($options) {
                    return $options['form_params']['client_id'] === 'client-id-123'
                        && $options['form_params']['client_secret'] === 'client-secret-123'
                        && $options['form_params']['scope'] === 'https://graph.microsoft.com/.default'
                        && $options['form_params']['grant_type'] === 'client_credentials'
                        && $options['http_errors'] === false;
                })
            )
            ->andReturn($tokenResponse);

        $provider = new MicrosoftGraphTokenProvider([
            'tenant_id' => 'tenant-id-123',
            'key' => 'client-id-123',
            'secret' => 'client-secret-123',
        ], 1, $guzzle);

        $this->assertSame('token-from-azure', $provider->getAccessToken());
        $this->assertSame('token-from-azure', $provider->getAccessToken());
    }
}
