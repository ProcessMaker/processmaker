<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Services\SmartExtractConfiguration;
use Tests\TestCase;

class SmartExtractConfigurationTest extends TestCase
{
    public function test_it_loads_and_decrypts_all_values_with_one_query(): void
    {
        $this->createConfigurationVariables();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $configuration = new SmartExtractConfiguration();

        $this->assertSame('https://extract.example.com', $configuration->apiHost());
        $this->assertSame('client-id', $configuration->clientId());
        $this->assertSame('client-secret', $configuration->clientSecret());
        $this->assertSame('https://dashboard.example.com/edit.html', $configuration->dashboardUrl());
        $this->assertTrue($configuration->hitlEnabled());

        $queries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'environment_variables'));

        $this->assertCount(1, $queries);
    }

    public function test_missing_empty_and_invalid_values_fail_closed(): void
    {
        config([
            'smart-extract.api_host' => null,
            'smart-extract.client_id' => null,
            'smart-extract.client_secret' => null,
            'smart-extract.dashboard_url' => null,
            'smart-extract.hitl_enabled' => false,
        ]);

        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::HITL_ENABLED,
            'value' => 'not-a-boolean',
        ]);
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::DASHBOARD_URL,
            'value' => '   ',
        ]);

        $configuration = new SmartExtractConfiguration();

        $this->assertFalse($configuration->hitlEnabled());
        $this->assertNull($configuration->apiHost());
        $this->assertNull($configuration->clientId());
        $this->assertNull($configuration->clientSecret());
        $this->assertNull($configuration->dashboardUrl());
    }

    public function test_missing_database_values_fall_back_to_legacy_configuration(): void
    {
        config([
            'smart-extract.api_host' => 'https://legacy.example.com',
            'smart-extract.client_id' => 'legacy-client-id',
            'smart-extract.client_secret' => 'legacy-client-secret',
            'smart-extract.dashboard_url' => 'https://legacy.example.com/edit.html',
            'smart-extract.hitl_enabled' => true,
        ]);

        $configuration = new SmartExtractConfiguration();

        $this->assertSame('https://legacy.example.com', $configuration->apiHost());
        $this->assertSame('legacy-client-id', $configuration->clientId());
        $this->assertSame('legacy-client-secret', $configuration->clientSecret());
        $this->assertSame('https://legacy.example.com/edit.html', $configuration->dashboardUrl());
        $this->assertTrue($configuration->hitlEnabled());
    }

    public function test_existing_database_values_never_revive_the_legacy_fallback(): void
    {
        config([
            'smart-extract.api_host' => 'https://legacy.example.com',
            'smart-extract.client_id' => 'legacy-client-id',
            'smart-extract.client_secret' => 'legacy-client-secret',
            'smart-extract.dashboard_url' => 'https://legacy.example.com/edit.html',
            'smart-extract.hitl_enabled' => true,
        ]);

        foreach ([
            SmartExtractConfiguration::API_HOST => '',
            SmartExtractConfiguration::CLIENT_ID => null,
            SmartExtractConfiguration::CLIENT_SECRET => false,
            SmartExtractConfiguration::DASHBOARD_URL => '   ',
            SmartExtractConfiguration::HITL_ENABLED => 'false',
        ] as $name => $value) {
            EnvironmentVariable::factory()->create([
                'name' => $name,
                'value' => $value,
            ]);
        }

        $configuration = new SmartExtractConfiguration();

        $this->assertNull($configuration->apiHost());
        $this->assertNull($configuration->clientId());
        $this->assertNull($configuration->clientSecret());
        $this->assertNull($configuration->dashboardUrl());
        $this->assertFalse($configuration->hitlEnabled());
    }

    public function test_scoped_configuration_refreshes_on_the_next_lifecycle(): void
    {
        $apiHost = EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::API_HOST,
            'value' => 'https://first.example.com',
        ]);

        $currentLifecycle = app(SmartExtractConfiguration::class);
        $this->assertSame('https://first.example.com', $currentLifecycle->apiHost());

        $apiHost->value = 'https://second.example.com';
        $apiHost->save();

        $this->assertSame('https://first.example.com', $currentLifecycle->apiHost());

        app()->forgetScopedInstances();
        $nextLifecycle = app(SmartExtractConfiguration::class);

        $this->assertNotSame($currentLifecycle, $nextLifecycle);
        $this->assertSame('https://second.example.com', $nextLifecycle->apiHost());
    }

    private function createConfigurationVariables(): void
    {
        foreach ([
            SmartExtractConfiguration::API_HOST => 'https://extract.example.com',
            SmartExtractConfiguration::CLIENT_ID => 'client-id',
            SmartExtractConfiguration::CLIENT_SECRET => 'client-secret',
            SmartExtractConfiguration::DASHBOARD_URL => 'https://dashboard.example.com/edit.html',
            SmartExtractConfiguration::HITL_ENABLED => 'true',
        ] as $name => $value) {
            EnvironmentVariable::factory()->create([
                'name' => $name,
                'value' => $value,
            ]);
        }
    }
}
