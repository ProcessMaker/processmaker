<?php

namespace Tests\Feature\Api;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessIntelligenceControllerTest extends TestCase
{
    use RequestHelper;

    public function testGetJweToken()
    {
        $route = route('api.process-intelligence.get-jwe-token');

        $response = $this->apiCall('GET', $route);

        $response->assertStatus(200);
        $response->assertJsonStructure(
            [
                'token',
            ]
        );
    }
}
