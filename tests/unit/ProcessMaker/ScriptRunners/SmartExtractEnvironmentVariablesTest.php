<?php

namespace Tests\Unit\ProcessMaker\ScriptRunners;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\Base;
use ProcessMaker\ScriptRunners\ScriptMicroserviceRunner;
use ProcessMaker\Services\SmartExtractConfiguration;
use ReflectionMethod;
use Tests\TestCase;

class SmartExtractEnvironmentVariablesTest extends TestCase
{
    public function test_local_runner_preserves_database_api_host_without_duplicates(): void
    {
        $this->createApiHost();
        $executor = ScriptExecutor::factory()->create(['language' => 'php']);
        $runner = new class ($executor) extends Base {
            public function config($code, array $dockerConfig)
            {
                return $dockerConfig;
            }
        };

        $method = new ReflectionMethod(Base::class, 'getEnvironmentVariables');
        $method->setAccessible(true);
        $variables = $method->invoke($runner, false);

        $apiHosts = array_values(array_filter(
            $variables,
            fn (string $variable) => str_starts_with($variable, SmartExtractConfiguration::API_HOST . '=')
        ));

        $this->assertSame([
            SmartExtractConfiguration::API_HOST . '=https://database.example.com',
        ], $apiHosts);
    }

    public function test_microservice_runner_preserves_database_api_host(): void
    {
        $this->createApiHost();
        $script = Script::factory()->create(['language' => 'php']);
        $user = User::factory()->create();
        Cache::put('script-runner-' . $user->id, 'access-token');

        $runner = new ScriptMicroserviceRunner($script);
        $method = new ReflectionMethod(ScriptMicroserviceRunner::class, 'getEnvironmentVariables');
        $method->setAccessible(true);
        $variables = $method->invoke($runner, $user);

        $this->assertSame(
            'https://database.example.com',
            $variables[SmartExtractConfiguration::API_HOST]
        );
    }

    private function createApiHost(): void
    {
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::API_HOST,
            'value' => 'https://database.example.com',
        ]);
    }
}
