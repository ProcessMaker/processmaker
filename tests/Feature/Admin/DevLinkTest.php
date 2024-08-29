<?php

namespace Tests\Feature\Admin;

use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use ProcessMaker\Models\DevLink;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    public function testIndex()
    {
        // Stores the client credentials if they are present in the request
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
