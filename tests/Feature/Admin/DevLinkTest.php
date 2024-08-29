<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use ProcessMaker\Models\DevLink;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    public function testIndexStoreClientCredentials()
    {
        $devLink = DevLink::factory()->create();

        $params = [
            'devlink_id' => $devLink->id,
            'client_id' => 123,
            'client_secret' => 'abc123',
        ];

        $response = $this->webCall('GET', route('devlink.index', $params));

        $devLink->refresh();

        $expectedRedirectParams = [
            'client_id' => 123,
            'redirect_uri' => route('devlink.index'),
            'response_type' => 'code',
            'state' => $devLink->state,
        ];
        $expectedRedirectUrl = $devLink->url . '/oauth/authorize?' . http_build_query($expectedRedirectParams);
        $response->assertRedirect($expectedRedirectUrl);

        $this->assertEquals($devLink->client_id, $params['client_id']);
        $this->assertEquals($devLink->client_secret, $params['client_secret']);
    }

    public function testIndexStoreOauthCredentials()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'http://remote-instance.test',
        ]);

        // Generate state uuid
        $devLink->getOauthRedirectUrl();

        $getTokenUrl = $devLink->url . '/oauth/token';
        Http::fake([
            $getTokenUrl => Http::response([
                'access_token' => '123abc',
                'refresh_token' => '456def',
                'expires_in' => 3600,
            ]),
        ]);

        $params = [
            'state' => $devLink->state,
            'code' => '12345',
        ];
        $response = $this->webCall('GET', route('devlink.index', $params));
        $response->assertStatus(200);

        $devLink->refresh();
        $this->assertEquals($devLink->access_token, '123abc');
        $this->assertEquals($devLink->refresh_token, '456def');
        $this->assertEquals($devLink->expires_in, 3600);
    }

    public function testGetOauthClient()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'http://placeholder.test',
        ]);
        $url = $devLink->getClientUrl();
        $url = str_replace('http://placeholder.test', '', $url);

        $response = $this->webCall('GET', $url);

        $response->assertStatus(302);

        $lastCreatedClient = Client::orderBy('id', 'desc')->first();
        $expectedParams = [
            'devlink_id' => $devLink->id,
            'client_id' => $lastCreatedClient->id,
            'client_secret' => $lastCreatedClient->secret,
        ];
        $response->assertRedirect(route('devlink.index', $expectedParams));
    }
}
