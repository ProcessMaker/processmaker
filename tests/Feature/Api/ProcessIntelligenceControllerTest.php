<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessIntelligenceControllerTest extends TestCase
{
    use WithFaker;
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
