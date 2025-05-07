<?php

namespace Tests\Feature\Api;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use ProcessMaker\Models\DataSourceIntegrations;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DataSourceIntegrationsTest extends TestCase
{
    use RefreshDatabase;
    use RequestHelper;

    public function testItCreatesDataSourceIntegration()
    {
        $payload = [
            'name' => 'Test Integration',
            'key' => 'test_key',
            'auth_type' => 'token',
            'base_url' => 'https://api.test.com',
            'credentials' => [
                'token' => 'test_token',
            ],
        ];

        $response = $this->apiCall('POST', route('api.data-source-integrations.store', $payload));

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Integration created successfully',
        ]);

        $this->assertDatabaseHas('data_source_integrations', [
            'name' => 'Test Integration',
            'key' => 'test_key',
        ]);
    }

    public function testItFailsValidationWithMissingRequiredFields()
    {
        $response = $this->apiCall('POST', route('api.data-source-integrations.store', []));

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'key', 'auth_type', 'base_url', 'credentials']);
    }
}
