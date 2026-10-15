<?php

namespace Tests\Feature\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Mockery;
use ProcessMaker\Console\Commands\TransitionExecutors;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ScriptExecutorVersion;
use ProcessMaker\Services\ScriptMicroserviceService;
use Tests\TestCase;
use WebSocket\Client;

class TransitionExecutorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ScriptExecutorVersion::where('id', '>', 0)->delete();
        ScriptExecutor::where('id', '>', 0)->delete();

        // Command must work even when the microservice feature flag is off.
        config([
            'script-runner-microservice.enabled' => false,
            'script-runner-microservice.broadcasting.app_key' => 'test-app-key',
            'script-runner-microservice.broadcasting.host' => 'broadcast.test',
        ]);
    }

    public function testTimeoutMustBeGreaterThanSixty(): void
    {
        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors', ['--timeout' => 60])
            ->expectsOutput('Timeout must be a greater or equal to 60')
            ->assertFailed();
    }

    public function testWarnsWhenNoExecutorsFound(): void
    {
        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors')
            ->expectsOutput('No script executors found to transition.')
            ->assertSuccessful();
    }

    public function testSkipsDefaultSystemAndUnsupportedExecutors(): void
    {
        ScriptExecutor::factory()->create([
            'title' => 'PHP Executor',
            'description' => 'Default PHP Executor',
            'language' => 'php',
            'config' => 'RUN echo default',
            'type' => null,
        ]);
        ScriptExecutor::factory()->create([
            'title' => 'System PHP',
            'description' => 'System executor',
            'language' => 'php',
            'config' => 'RUN echo system',
            'is_system' => true,
            'type' => ScriptExecutorType::System,
        ]);
        ScriptExecutor::factory()->create([
            'title' => 'Nayra',
            'description' => 'Nayra executor',
            'language' => 'php-nayra',
            'config' => 'RUN echo nayra',
            'type' => ScriptExecutorType::Custom,
        ]);
        ScriptExecutor::factory()->create([
            'title' => 'Null config',
            'description' => 'Missing config',
            'language' => 'php',
            'config' => null,
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->mock(ScriptMicroserviceService::class, function ($mock) {
            $mock->shouldNotReceive('updateCustomExecutor');
        });

        $this->artisan('processmaker:transition-executors')
            ->expectsOutput('No script executors found to transition.')
            ->assertSuccessful();
    }

    public function testTransitionsCustomExecutorSuccessfully(): void
    {
        $executor = $this->createTransitionableExecutor([
            'title' => 'Custom PHP',
            'description' => 'A custom PHP executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($executor) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                    ->andReturn(['status' => 'success']);
            },
            [
                ['event' => 'build-image', 'data' => 'Building...'],
                ['event' => 'build-finished', 'data' => 'done'],
            ]
        );

        $this->artisan('processmaker:transition-executors', [
            '--uuid' => [$executor->uuid],
            '--timeout' => 120,
        ])
            ->expectsOutput("Transitioning executor {$executor->uuid} (php) to the microservice...")
            ->expectsOutput('Building...')
            ->expectsOutput("Build finished for executor {$executor->uuid} - done")
            ->expectsOutput("Executor {$executor->uuid} transitioned successfully." . PHP_EOL)
            ->assertSuccessful();
    }

    public function testTransitionsNonDefaultNullTypeExecutor(): void
    {
        $executor = $this->createTransitionableExecutor([
            'title' => 'Extra PHP',
            'description' => 'Extra null type PHP',
            'language' => 'php',
            'type' => null,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($executor) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                    ->andReturn(['status' => 'updated']);
            },
            [
                ['event' => 'build-finished', 'data' => 'ok'],
            ]
        );

        $this->artisan('processmaker:transition-executors', ['--uuid' => [$executor->uuid]])
            ->expectsOutput("Executor {$executor->uuid} transitioned successfully." . PHP_EOL)
            ->assertSuccessful();
    }

    public function testFiltersByUuidOption(): void
    {
        $included = $this->createTransitionableExecutor([
            'title' => 'Included',
            'description' => 'Included executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);
        $excluded = $this->createTransitionableExecutor([
            'title' => 'Excluded',
            'description' => 'Excluded executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($included, $excluded) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(function (ScriptExecutor $passed) use ($included, $excluded) {
                        $this->assertSame($included->uuid, $passed->uuid);
                        $this->assertNotSame($excluded->uuid, $passed->uuid);

                        return true;
                    })
                    ->andReturn(['status' => 'success']);
            },
            [
                ['event' => 'build-finished', 'data' => 'ok'],
            ]
        );

        $this->artisan('processmaker:transition-executors', ['--uuid' => [$included->uuid]])
            ->expectsOutput("Executor {$included->uuid} transitioned successfully." . PHP_EOL)
            ->assertSuccessful();
    }

    public function testBuildErrorDoesNotReportSuccess(): void
    {
        $executor = $this->createTransitionableExecutor([
            'title' => 'Broken build',
            'description' => 'Broken build executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($executor) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                    ->andReturn(['status' => 'success']);
            },
            [
                ['event' => 'build-error', 'data' => 'docker failed'],
            ]
        );

        $this->artisan('processmaker:transition-executors', ['--uuid' => [$executor->uuid]])
            ->expectsOutput("Error occurred while building image for executor {$executor->uuid} - docker failed")
            ->doesntExpectOutput("Executor {$executor->uuid} transitioned successfully." . PHP_EOL)
            ->assertSuccessful();
    }

    public function testRequestExceptionIsReportedAndCommandContinues(): void
    {
        $executor = $this->createTransitionableExecutor([
            'title' => 'Request fails',
            'description' => 'Request fails executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($executor) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                    ->andReturnUsing(function () {
                        $response = new Response(
                            new \GuzzleHttp\Psr7\Response(500, [], 'SDK build failed hard')
                        );

                        throw new RequestException($response);
                    });
            },
            []
        );

        $this->artisan('processmaker:transition-executors', ['--uuid' => [$executor->uuid]])
            ->expectsOutput("Request failed for executor {$executor->uuid}")
            ->expectsOutput('SDK build failed hard')
            ->assertSuccessful();
    }

    public function testGenericThrowableIsReportedAndCommandContinues(): void
    {
        $executor = $this->createTransitionableExecutor([
            'title' => 'Throwable fails',
            'description' => 'Throwable fails executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($executor) {
                $service->shouldReceive('updateCustomExecutor')
                    ->once()
                    ->withArgs(fn (ScriptExecutor $passed) => $passed->uuid === $executor->uuid)
                    ->andThrow(new \RuntimeException('websocket boom'));
            },
            []
        );

        $this->artisan('processmaker:transition-executors', ['--uuid' => [$executor->uuid]])
            ->expectsOutput("Transition failed for executor {$executor->uuid}")
            ->expectsOutput('websocket boom')
            ->assertSuccessful();
    }

    public function testTransitionsMultipleExecutors(): void
    {
        $first = $this->createTransitionableExecutor([
            'title' => 'First custom',
            'description' => 'First custom executor',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);
        $second = $this->createTransitionableExecutor([
            'title' => 'Second custom',
            'description' => 'Second custom executor',
            'language' => 'javascript',
            'type' => ScriptExecutorType::Custom,
        ]);

        $this->registerCommandWithMocks(
            function ($service) use ($first, $second) {
                $service->shouldReceive('updateCustomExecutor')
                    ->twice()
                    ->andReturnUsing(function (ScriptExecutor $executor) use ($first, $second) {
                        if (!in_array($executor->uuid, [$first->uuid, $second->uuid], true)) {
                            $this->fail('Unexpected executor uuid: ' . $executor->uuid);
                        }

                        return ['status' => 'success'];
                    });
            },
            [
                ['event' => 'build-finished', 'data' => 'first done'],
                ['event' => 'build-finished', 'data' => 'second done'],
            ]
        );

        $this->artisan('processmaker:transition-executors')
            ->expectsOutput("Executor {$first->uuid} transitioned successfully." . PHP_EOL)
            ->expectsOutput("Executor {$second->uuid} transitioned successfully." . PHP_EOL)
            ->assertSuccessful();
    }

    private function createTransitionableExecutor(array $attributes = []): ScriptExecutor
    {
        return ScriptExecutor::factory()->create(array_merge([
            'config' => 'RUN echo custom',
            'is_system' => false,
        ], $attributes));
    }

    /**
     * Bind a command instance that uses mocked microservice + websocket collaborators.
     */
    private function registerCommandWithMocks(callable $configureService, array $messages): void
    {
        $service = Mockery::mock(ScriptMicroserviceService::class);
        $configureService($service);
        $this->app->instance(ScriptMicroserviceService::class, $service);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setTimeout')->andReturnNull();
        $client->shouldReceive('send')->andReturnNull();
        $client->shouldReceive('close')->andReturnNull();

        if ($messages === []) {
            $client->shouldReceive('receive')->never();
        } else {
            $client->shouldReceive('receive')
                ->times(count($messages))
                ->andReturnValues(array_map(
                    fn ($message) => json_encode($message),
                    $messages
                ));
        }

        $command = new class($service, $client) extends TransitionExecutors {
            public function __construct(
                ScriptMicroserviceService $scriptMicroserviceService,
                private Client $broadcastClient
            ) {
                parent::__construct($scriptMicroserviceService);
            }

            protected function createBroadcastClient(string $url, int $timeout): Client
            {
                $this->broadcastClient->setTimeout($timeout);

                return $this->broadcastClient;
            }
        };

        $command->setLaravel($this->app);
        $this->app->instance(TransitionExecutors::class, $command);
        $this->app->make(Kernel::class)->registerCommand($command);
    }
}
