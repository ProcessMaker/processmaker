<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Services\ScriptMicroserviceService;
use Tests\TestCase;

class ScriptMicroserviceServiceTest extends TestCase
{
    private ScriptMicroserviceService $service;

    private string $baseUrl;

    private string $instanceUuid;

    private string $accessToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'https://script-runner.test';
        $this->accessToken = 'test-access-token';

        // Set up config values
        Config::set('script-runner-microservice.base_url', $this->baseUrl);
        Config::set('script-runner-microservice.version', '1.0.0');
        Config::set('script-runner-microservice.keycloak.base_url', 'https://keycloak.test/token');
        Config::set('script-runner-microservice.keycloak.client_id', 'test-client');
        Config::set('script-runner-microservice.keycloak.client_secret', 'test-secret');
        Config::set('script-runner-microservice.keycloak.username', 'test-user');
        Config::set('script-runner-microservice.keycloak.password', 'test-password');
        Config::set('app.url', 'https://app.test');

        $this->service = new ScriptMicroserviceService();

        // Calculate the actual instance UUID that will be used
        $this->instanceUuid = $this->service->getInstanceUuid();

        // Clear cache before each test
        Cache::flush();
    }

    public function testCreateCustomExecutor()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'title' => 'Test Executor',
            'description' => 'Test Description',
            'language' => 'php',
            'config' => '{"test": "config"}',
        ]);

        // Set uuid property (assuming it exists or can be set)
        $scriptExecutor->uuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check - tenant exists
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 200),
            // Access token request
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Create executor request
            $this->baseUrl . '/custom/' . $this->instanceUuid . '/scripts' => Http::response([
                'id' => $scriptExecutor->uuid,
                'name' => $scriptExecutor->title,
                'status' => 'created',
            ], 201),
        ]);

        $result = $this->service->createCustomExecutor($scriptExecutor);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/custom/' . $this->instanceUuid . '/scripts')
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer ' . $this->accessToken)
                && isset($request->data()['id'])
                && $request->data()['name'] === 'Test Executor'
                && $request->data()['language'] === 'php'
                && $request->data()['version'] === '1.0.0';
        });

        $this->assertEquals('test-executor-uuid', $result['id']);
        $this->assertEquals('Test Executor', $result['name']);
        $this->assertEquals('created', $result['status']);
    }

    public function testUpdateCustomExecutor()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'title' => 'Updated Executor',
            'description' => 'Updated Description',
            'language' => 'javascript',
            'config' => '{"updated": "config"}',
        ]);

        $scriptExecutor->uuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check - tenant exists
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 200),
            // Access token request
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Update executor request
            $this->baseUrl . '/custom/scripts/' . $scriptExecutor->uuid => Http::response([
                'id' => $scriptExecutor->uuid,
                'name' => $scriptExecutor->title,
                'status' => 'updated',
            ], 200),
        ]);

        $result = $this->service->updateCustomExecutor($scriptExecutor);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/custom/scripts/test-executor-uuid')
                && $request->method() === 'PUT'
                && $request->hasHeader('Authorization', 'Bearer ' . $this->accessToken)
                && $request->data()['name'] === 'Updated Executor'
                && $request->data()['language'] === 'javascript'
                && $request->data()['version'] === '1.0.0';
        });

        $this->assertEquals('test-executor-uuid', $result['id']);
        $this->assertEquals('Updated Executor', $result['name']);
        $this->assertEquals('updated', $result['status']);
    }

    public function testUpdateCustomExecutorCreatesWhenNotFound()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'title' => 'New Executor',
            'description' => 'New Description',
            'language' => 'php',
            'config' => '{"new": "config"}',
        ]);

        $scriptExecutor->uuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check - tenant exists
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 200),
            // Access token request
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Update executor request - returns 404
            $this->baseUrl . '/custom/scripts/' . $scriptExecutor->uuid => Http::response([], 404),
            // Create executor request (should be called after 404)
            $this->baseUrl . '/custom/' . $this->instanceUuid . '/scripts' => Http::response([
                'id' => $scriptExecutor->uuid,
                'name' => $scriptExecutor->title,
                'status' => 'created',
            ], 201),
        ]);

        $result = $this->service->updateCustomExecutor($scriptExecutor);

        // Verify update was attempted first
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/custom/scripts/test-executor-uuid')
                && $request->method() === 'PUT';
        });

        // Verify create was called after 404
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/custom/' . $this->instanceUuid . '/scripts')
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer ' . $this->accessToken)
                && isset($request->data()['id'])
                && $request->data()['id'] === 'test-executor-uuid'
                && $request->data()['name'] === 'New Executor'
                && $request->data()['language'] === 'php'
                && $request->data()['version'] === '1.0.0';
        });

        $this->assertEquals('test-executor-uuid', $result['id']);
        $this->assertEquals('New Executor', $result['name']);
        $this->assertEquals('created', $result['status']);
    }

    public function testDeleteCustomExecutor()
    {
        $executorUuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check - tenant exists
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 200),
            // Access token request
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Delete executor request
            $this->baseUrl . '/custom/scripts/' . $executorUuid => Http::response([
                'status' => 'deleted',
            ], 200),
        ]);

        $result = $this->service->deleteCustomExecutor($executorUuid);

        Http::assertSent(function ($request) use ($executorUuid) {
            return str_contains($request->url(), '/custom/scripts/' . $executorUuid)
                && $request->method() === 'DELETE'
                && $request->hasHeader('Authorization', 'Bearer ' . $this->accessToken);
        });

        $this->assertEquals('deleted', $result['status']);
    }

    public function testCheckTenantCreatesTenantWhenNotFound()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'title' => 'Test Executor',
            'description' => 'Test Description',
            'language' => 'php',
            'config' => '{}',
        ]);

        $scriptExecutor->uuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check - tenant does not exist (404)
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 404),
            // Tenant creation
            $this->baseUrl . '/tenants' => Http::response([
                'id' => $this->instanceUuid,
                'name' => $this->instanceUuid,
            ], 201),
            // Access token request
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Create executor request
            $this->baseUrl . '/custom/' . $this->instanceUuid . '/scripts' => Http::response([
                'id' => $scriptExecutor->uuid,
                'name' => $scriptExecutor->title,
                'status' => 'created',
            ], 201),
        ]);

        $result = $this->service->createCustomExecutor($scriptExecutor);

        // Verify tenant was created
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/tenants')
                && $request->method() === 'POST'
                && isset($request->data()['id'])
                && $request->data()['id'] === $this->instanceUuid
                && $request->data()['name'] === $this->instanceUuid;
        });

        // Verify executor was created after tenant creation
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/custom/' . $this->instanceUuid . '/scripts')
                && $request->method() === 'POST';
        });

        $this->assertEquals('test-executor-uuid', $result['id']);
    }

    public function testAccessTokenIsCached()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'title' => 'Test Executor',
            'description' => 'Test Description',
            'language' => 'php',
            'config' => '{}',
        ]);

        $scriptExecutor->uuid = 'test-executor-uuid';

        Http::fake([
            // Tenant check
            $this->baseUrl . '/tenants/' . $this->instanceUuid => Http::response([], 200),
            // Access token request - should only be called once
            'https://keycloak.test/token' => Http::response([
                'access_token' => $this->accessToken,
                'expires_in' => 3600,
            ], 200),
            // Create executor request
            $this->baseUrl . '/custom/' . $this->instanceUuid . '/scripts' => Http::response([
                'id' => $scriptExecutor->uuid,
                'status' => 'created',
            ], 201),
        ]);

        // First call - should fetch token
        $this->service->createCustomExecutor($scriptExecutor);

        // Second call - should use cached token
        $this->service->createCustomExecutor($scriptExecutor);

        // Verify token was only requested once by checking recorded requests
        $recorded = Http::recorded();
        $tokenRequests = array_filter($recorded->toArray(), function ($record) {
            return str_contains($record[0]->url(), 'keycloak.test/token');
        });

        $this->assertCount(1, $tokenRequests, 'Access token should only be requested once');
    }
}
