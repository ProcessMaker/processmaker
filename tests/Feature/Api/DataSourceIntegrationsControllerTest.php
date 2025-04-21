<?php

namespace Tests\Feature\Api;

use Mockery;
use ProcessMaker\Exception\DataSourceIntegrationException\UnsupportedDataSourceException;
use ProcessMaker\Models\User;
use ProcessMaker\Services\DataSourceIntegrations\DataSourceIntegrationsService;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DataSourceIntegrationsControllerTest extends TestCase
{
    use RequestHelper;

    protected $service;

    public function setUp(): void
    {
        parent::setUp();
        // Create and authenticate a user
        $this->user = User::factory()->create([
            'is_administrator' => true,
        ]);
        $this->actingAs($this->user);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItReturnsParametersForSpecificSource()
    {
        $source = 'pitchbook';
        $expectedParameters = ['apiKey' => ['type' => 'string']];

        // Get the original service (singleton)
        $originalService = app(DataSourceIntegrationsService::class);

        // Create a mock based on the original service
        $mockService = Mockery::mock($originalService);

        // Set expectations for the mock
        $mockService->shouldReceive('setSource')
            ->with($source)
            ->once()
            ->andReturnSelf();

        $mockService->shouldReceive('getParameters')
            ->once()
            ->andReturn($expectedParameters);

        // Replace the service just for this test
        app()->instance(DataSourceIntegrationsService::class, $mockService);

        // Make the request
        $response = $this->apiCall('GET',
            route('api.data-source-integrations.parameters'),
            ['source' => $source]
        );

        // Assert response
        $response->assertStatus(200);
        $response->assertJson($expectedParameters);
    }

    public function testItReturnsParametersForAllSources()
    {
        $allParameters = [
            'pitchbook' => ['apiKey' => ['type' => 'string']],
            'crunchbase' => ['apiKey' => ['type' => 'string']],
        ];

        $originalService = app(DataSourceIntegrationsService::class);

        $mockService = Mockery::mock($originalService);

        $mockService->shouldReceive('getParameters')
            ->withNoArgs()
            ->once()
            ->andReturn($allParameters);

        app()->instance(DataSourceIntegrationsService::class, $mockService);

        $response = $this->apiCall('GET', route('api.data-source-integrations.parameters'));

        $response->assertStatus(200);
        $response->assertJson($allParameters);
    }

    public function testItHandlesUnsupportedDataSourceException()
    {
        // Get the actual singleton instance
        $originalService = app(DataSourceIntegrationsService::class);

        // Create a test double with the same methods
        $mockService = Mockery::mock($originalService);

        $mockService->shouldReceive('setSource')
            ->with('invalid-source')
            ->once()
            ->andReturnSelf();

        $mockService->shouldReceive('getParameters')
            ->once()
            ->andThrow(new UnsupportedDataSourceException('invalid-source'));

        // Replace the service just for this test
        app()->instance(DataSourceIntegrationsService::class, $mockService);

        $response = $this->apiCall('GET',
            route('api.data-source-integrations.parameters'),
            ['source' => 'invalid-source']
        );

        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'message']);
    }

    public function testItReturnsCompaniesForSpecificSource()
    {
        $source = 'pitchbook';
        $expectedCompanies = [
            ['id' => '1', 'name' => 'Company A'],
            ['id' => '2', 'name' => 'Company B'],
        ];

        $originalService = app(DataSourceIntegrationsService::class);

        $mockService = Mockery::mock($originalService);

        $mockService->shouldReceive('setSource')
            ->with($source)
            ->once()
            ->andReturnSelf();

        $mockService->shouldReceive('getCompanies')
            ->once()
            ->andReturn($expectedCompanies);

        app()->instance(DataSourceIntegrationsService::class, $mockService);

        $response = $this->apiCall('GET',
            route('api.data-source-integrations.companies'),
            array_merge(['source' => $source])
        );

        $response->assertStatus(200);
        $response->assertJson($expectedCompanies);
    }

    public function testItReturnsCompaniesForAllSources()
    {
        $expectedCompanies = [
            'pitchbook' => [['id' => '1', 'name' => 'Company A']],
            'crunchbase' => [['id' => '2', 'name' => 'Company B']],
        ];

        $originalService = app(DataSourceIntegrationsService::class);

        $mockService = Mockery::mock($originalService);

        $mockService->shouldReceive('getCompanies')
            ->withNoArgs()
            ->once()
            ->andReturn($expectedCompanies);

        app()->instance(DataSourceIntegrationsService::class, $mockService);

        $response = $this->apiCall('GET', route('api.data-source-integrations.companies'));

        $response->assertStatus(200);
        $response->assertJson($expectedCompanies);
    }

    public function testItFetchesCompanyDetails()
    {
        $source = 'pitchbook';
        $companyId = 'abc123';
        $expectedDetails = [
            'name' => 'Company A',
            'description' => 'Detailed description',
            'revenue' => 1000000,
            'employees' => 50,
            'founded_year' => 2010,
        ];

        $originalService = app(DataSourceIntegrationsService::class);

        $mockService = Mockery::mock($originalService);

        $mockService->shouldReceive('setSource');

        $mockService->shouldReceive('fetchCompanyDetails')
            ->with($source, $companyId)
            ->once()
            ->andReturn($expectedDetails);

        app()->instance(DataSourceIntegrationsService::class, $mockService);

        $response = $this->apiCall('GET',
            route('api.data-source-integrations.company-details', [
                'source' => $source,
                'companyId' => $companyId,
            ])
        );

        $response->assertStatus(200);
        $response->assertJson($expectedDetails);
    }
}
