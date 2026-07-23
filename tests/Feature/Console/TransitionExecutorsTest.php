<?php

namespace Tests\Feature\Console;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ScriptExecutorVersion;
use ProcessMaker\Services\ScriptMicroserviceService;
use Tests\TestCase;

class TransitionExecutorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ScriptExecutorVersion::where('id', '>', 0)->delete();
        ScriptExecutor::where('id', '>', 0)->delete();

        // Command must work even when the microservice feature flag is off.
        config(['script-runner-microservice.enabled' => false]);
    }

    public function testInvalidUuidFails(): void
    {
        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => 'not-a-uuid'])
            ->expectsOutput('Invalid uuid. Provide a script executor UUID or "all".')
            ->assertFailed();
    }

    public function testNumericIdIsRejected(): void
    {
        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => '5'])
            ->expectsOutput('Invalid uuid. Provide a script executor UUID or "all".')
            ->assertFailed();
    }

    public function testMissingExecutorFails(): void
    {
        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $missingUuid = '00000000-0000-4000-8000-000000000099';

        $this->artisan('processmaker:transition-executors', ['uuid' => $missingUuid])
            ->expectsOutput("Script executor [{$missingUuid}] not found.")
            ->assertFailed();
    }

    public function testDefaultExecutorFails(): void
    {
        $default = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'PHP Executor',
            'type' => null,
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => $default->uuid])
            ->expectsOutput("Script executor [{$default->uuid}] is a default/system executor and cannot be transitioned.")
            ->assertFailed();
    }

    public function testAllSkipsDefaultsAndIncludesCustomAndNonDefaultNullType(): void
    {
        $defaultPhp = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Default PHP',
            'type' => null,
            'created_at' => now()->subDay(),
        ]);
        $defaultJs = ScriptExecutor::factory()->create([
            'language' => 'javascript',
            'title' => 'Default JS',
            'type' => null,
            'created_at' => now()->subDay(),
        ]);
        $custom = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Custom PHP',
            'type' => ScriptExecutorType::Custom,
            'created_at' => now(),
        ]);
        $extraNullPhp = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Extra null PHP',
            'type' => null,
            'created_at' => now()->addMinute(),
        ]);
        ScriptExecutor::factory()->create([
            'language' => 'python',
            'title' => 'System Python',
            'type' => ScriptExecutorType::System,
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) use ($custom, $extraNullPhp, $defaultPhp, $defaultJs) {
            $mock->shouldReceive('updateCustomExecutor')
                ->twice()
                ->andReturnUsing(function (ScriptExecutor $executor) use ($custom, $extraNullPhp, $defaultPhp, $defaultJs) {
                    if (in_array($executor->uuid, [$defaultPhp->uuid, $defaultJs->uuid], true)) {
                        $this->fail('Default executors should not be transitioned');
                    }

                    if (!in_array($executor->uuid, [$custom->uuid, $extraNullPhp->uuid], true)) {
                        $this->fail('Unexpected executor uuid: ' . $executor->uuid);
                    }

                    return ['status' => 'success'];
                });
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => 'all'])
            ->expectsOutput("Executor {$custom->uuid} transitioned successfully.")
            ->expectsOutput("Executor {$extraNullPhp->uuid} transitioned successfully.")
            ->expectsOutput('All script executors transitioned successfully. (2 processed)')
            ->assertSuccessful();
    }

    public function testSingleCustomExecutorSuccessByUuid(): void
    {
        // Seed a default first so custom is not treated as the language default.
        ScriptExecutor::factory()->create([
            'language' => 'php',
            'type' => null,
            'created_at' => now()->subDay(),
        ]);

        $executor = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'PHP Executor',
            'config' => 'RUN echo custom',
            'type' => ScriptExecutorType::Custom,
            'created_at' => now(),
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) use ($executor) {
            $mock->shouldReceive('updateCustomExecutor')
                ->once()
                ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                ->andReturn(['status' => 'success']);
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => $executor->uuid])
            ->expectsOutput("Transitioning executor {$executor->uuid} (php) to the microservice...")
            ->expectsOutput("Executor {$executor->uuid} transitioned successfully.")
            ->assertSuccessful();
    }

    public function testSingleNonDefaultNullTypeSucceeds(): void
    {
        ScriptExecutor::factory()->create([
            'language' => 'php',
            'type' => null,
            'created_at' => now()->subDay(),
        ]);

        $executor = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Extra PHP',
            'type' => null,
            'created_at' => now(),
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) use ($executor) {
            $mock->shouldReceive('updateCustomExecutor')
                ->once()
                ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                ->andReturn(['status' => 'success']);
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => $executor->uuid])
            ->expectsOutput("Executor {$executor->uuid} transitioned successfully.")
            ->assertSuccessful();
    }

    public function testAllStopsOnStatusError(): void
    {
        $first = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'First',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);
        $second = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Second',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);
        $third = ScriptExecutor::factory()->create([
            'language' => 'php',
            'title' => 'Third',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) use ($first, $second, $third) {
            $mock->shouldReceive('updateCustomExecutor')
                ->times(2)
                ->andReturnUsing(function (ScriptExecutor $executor) use ($first, $second, $third) {
                    if ($executor->uuid === $third->uuid) {
                        $this->fail('Third executor should not be transitioned after a failure');
                    }

                    if ($executor->uuid === $first->uuid) {
                        return ['status' => 'success'];
                    }

                    if ($executor->uuid === $second->uuid) {
                        return [
                            'status' => 'error',
                            'sdk_output' => 'SDK generation failed: boom',
                            'docker_output' => 'Docker build failed: boom',
                        ];
                    }

                    $this->fail('Unexpected executor uuid: ' . $executor->uuid);
                });
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => 'all'])
            ->expectsOutput("Executor {$first->uuid} transitioned successfully.")
            ->expectsOutput("Transition failed for executor {$second->uuid}")
            ->expectsOutput('Stopping: 1 remaining executor(s) were not processed.')
            ->assertFailed();
    }

    public function testAllStopsOnRequestException(): void
    {
        $first = ScriptExecutor::factory()->create([
            'language' => 'php',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);
        $second = ScriptExecutor::factory()->create([
            'language' => 'php',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);
        $third = ScriptExecutor::factory()->create([
            'language' => 'php',
            'config' => '',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) use ($first, $second, $third) {
            $mock->shouldReceive('updateCustomExecutor')
                ->times(2)
                ->andReturnUsing(function (ScriptExecutor $executor) use ($first, $second, $third) {
                    if ($executor->uuid === $third->uuid) {
                        $this->fail('Third executor should not be transitioned after a failure');
                    }

                    if ($executor->uuid === $first->uuid) {
                        return ['status' => 'success'];
                    }

                    if ($executor->uuid === $second->uuid) {
                        $response = new Response(
                            new \GuzzleHttp\Psr7\Response(500, [], 'SDK build failed hard')
                        );

                        throw new RequestException($response);
                    }

                    $this->fail('Unexpected executor uuid: ' . $executor->uuid);
                });
        });

        $this->artisan('processmaker:transition-executors', ['uuid' => 'all'])
            ->expectsOutput("Executor {$first->uuid} transitioned successfully.")
            ->expectsOutput("Transition failed for executor {$second->uuid}")
            ->expectsOutput('SDK build failed hard')
            ->expectsOutput('Stopping: 1 remaining executor(s) were not processed.')
            ->assertFailed();
    }
}
