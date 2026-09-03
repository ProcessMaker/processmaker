<?php

namespace Tests\Unit\ProcessMaker\ScriptRunners;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\Base;
use ProcessMaker\ScriptRunners\ScriptMicroserviceRunner;
use ProcessMaker\ScriptRunners\ScriptRunner;
use ProcessMaker\Services\SmartExtractConfiguration;
use ReflectionMethod;
use ReflectionProperty;
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

    public function test_both_runners_propagate_the_legacy_api_host_fallback(): void
    {
        config(['smart-extract.api_host' => 'https://legacy.example.com']);

        $executor = ScriptExecutor::factory()->create(['language' => 'php']);
        $localRunner = new class ($executor) extends Base {
            public function config($code, array $dockerConfig)
            {
                return $dockerConfig;
            }
        };
        $localMethod = new ReflectionMethod(Base::class, 'getEnvironmentVariables');
        $localMethod->setAccessible(true);
        $localVariables = $localMethod->invoke($localRunner, false);

        $this->assertSame([
            SmartExtractConfiguration::API_HOST . '=https://legacy.example.com',
        ], array_values(array_filter(
            $localVariables,
            fn (string $variable) => str_starts_with($variable, SmartExtractConfiguration::API_HOST . '=')
        )));

        $script = Script::factory()->create(['language' => 'php']);
        $user = User::factory()->create();
        Cache::put('script-runner-' . $user->id, 'access-token');
        $microserviceRunner = new ScriptMicroserviceRunner($script);
        $microserviceMethod = new ReflectionMethod(ScriptMicroserviceRunner::class, 'getEnvironmentVariables');
        $microserviceMethod->setAccessible(true);
        $microserviceVariables = $microserviceMethod->invoke($microserviceRunner, $user);

        $this->assertSame(
            'https://legacy.example.com',
            $microserviceVariables[SmartExtractConfiguration::API_HOST]
        );
    }

    public function test_existing_empty_database_api_host_suppresses_fallback_in_both_runners(): void
    {
        config(['smart-extract.api_host' => 'https://legacy.example.com']);
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::API_HOST,
            'value' => '',
        ]);

        $executor = ScriptExecutor::factory()->create(['language' => 'php']);
        $localRunner = new class ($executor) extends Base {
            public function config($code, array $dockerConfig)
            {
                return $dockerConfig;
            }
        };
        $localMethod = new ReflectionMethod(Base::class, 'getEnvironmentVariables');
        $localMethod->setAccessible(true);
        $localVariables = $localMethod->invoke($localRunner, false);

        $this->assertSame([], array_values(array_filter(
            $localVariables,
            fn (string $variable) => str_starts_with($variable, SmartExtractConfiguration::API_HOST . '=')
        )));

        $script = Script::factory()->create(['language' => 'php']);
        $user = User::factory()->create();
        Cache::put('script-runner-' . $user->id, 'access-token');
        $microserviceRunner = new ScriptMicroserviceRunner($script);
        $microserviceMethod = new ReflectionMethod(ScriptMicroserviceRunner::class, 'getEnvironmentVariables');
        $microserviceMethod->setAccessible(true);
        $microserviceVariables = $microserviceMethod->invoke($microserviceRunner, $user);

        $this->assertArrayNotHasKey(SmartExtractConfiguration::API_HOST, $microserviceVariables);
    }

    public function test_smart_extract_docker_failure_log_omits_raw_output(): void
    {
        $executor = ScriptExecutor::factory()->create([
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);
        $script = Script::factory()->create([
            'key' => SmartExtractConfiguration::SEND_DOCUMENT_SCRIPT_KEY,
            'language' => 'php',
            'script_executor_id' => $executor->id,
        ]);
        config([
            'script-runner-microservice.enabled' => false,
            'script-runners.php.runner' => 'PhpRunner',
        ]);

        $scriptRunner = new ScriptRunner($script);
        $runnerProperty = new ReflectionProperty(ScriptRunner::class, 'runner');
        $runnerProperty->setAccessible(true);
        $runner = $runnerProperty->getValue($scriptRunner);

        $message = $this->dockerFailureLogMessage($runner, [
            'Authorization: Basic dXNlcjpwYXNz',
            'Bearer secret-token',
        ]);

        $this->assertSame('Script threw return code 255', $message);
        $this->assertStringNotContainsString('dXNlcjpwYXNz', $message);
        $this->assertStringNotContainsString('secret-token', $message);
    }

    public function test_other_docker_failure_logs_keep_their_raw_output(): void
    {
        $executor = ScriptExecutor::factory()->create(['language' => 'php']);
        $runner = new class ($executor, 'another-package/script') extends Base {
            public function config($code, array $dockerConfig)
            {
                return $dockerConfig;
            }
        };

        $message = $this->dockerFailureLogMessage($runner, ['Existing diagnostic']);

        $this->assertSame('Script threw return code 255 Message: Existing diagnostic', $message);
    }

    private function createApiHost(): void
    {
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::API_HOST,
            'value' => 'https://database.example.com',
        ]);
    }

    private function dockerFailureLogMessage(Base $runner, array $output): string
    {
        $method = new ReflectionMethod(Base::class, 'dockerFailureLogMessage');
        $method->setAccessible(true);

        return $method->invoke($runner, 255, $output);
    }
}
