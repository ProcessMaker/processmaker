<?php

namespace Tests\Feature\Admin;

use Laravel\Passport\Client;
use ProcessMaker\Models\DevLink;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    public function testIndex()
    {
        $devLink = DevLink::factory()->create();
        $params = [
            'devlink_id' => $devLink->id,
            'client_id' => 123,
            'client_secret' => 'abc123',
        ];

        $response = $this->webCall('GET', route('devlink.index', $params));
        $response->assertRedirect($devLink->getOauthRedirectUrl());

        $devLink->refresh();
        $this->assertEquals($devLink->client_id, $params['client_id']);
        $this->assertEquals($devLink->client_secret, $params['client_secret']);
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
