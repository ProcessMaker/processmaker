<?php

namespace Tests\Feature\ProcessMaker\ScriptRunners;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptMicroserviceRunner;
use RuntimeException;
use Tests\TestCase;

class ScriptMicroserviceRunnerTest extends TestCase
{
    protected $script;

    protected $user;

    protected $runner;

    protected $fakeResponses;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@processmaker.com',
        ]);

        // Create script executor
        $executor = ScriptExecutor::factory()->create([
            'language' => 'php',
        ]);

        // Create test script
        $this->script = Script::factory()->create([
            'language' => 'php',
            'script_executor_id' => $executor->id,
        ]);

        // Initialize runner
        $this->runner = new ScriptMicroserviceRunner($this->script);

        // Clear cache
        Cache::forget('keycloak.access_token');
        Cache::forget('script-runner-microservice.script-languages');

        // Setup default fake responses
        $this->fakeResponses = [
            '*sso-microsvr.processmaker.net*' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 300,
            ], 200),
            '*script-runner.processmaker.net/scripts*' => Http::response([
                ['language' => 'php', 'version' => '1.0.0'],
                ['language' => 'node', 'version' => '1.0.0'],
            ], 200),
            '*script-runner.processmaker.net/requests/create*' => Http::response([
                'output' => ['result' => 'success'],
            ], 200),
        ];

        // Apply fake responses globally
        Http::fake($this->fakeResponses);
    }

    /** @test */
    public function it_handles_failed_response_with_error_output()
    {
        // Create a mock response with error output
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->withNoArgs()
            ->andReturn([
                'output' => [
                    'error' => 'syntax error, unexpected token "return"',
                ],
            ]);
        $response->shouldReceive('body')
            ->andReturn(json_encode([
                'output' => [
                    'error' => 'syntax error, unexpected token "return"',
                ],
            ]));

        // Use reflection to access private method
        $method = new \ReflectionMethod(ScriptMicroserviceRunner::class, 'handleFailedResponse');
        $method->setAccessible(true);

        // Assert exception is thrown with correct message
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Script execution failed: syntax error, unexpected token "return"');

        // Execute the private method
        $method->invoke($this->runner, $response);
    }

    /** @test */
    public function it_handles_failed_response_with_raw_body()
    {
        // Create a mock response with raw body
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('json')
            ->withNoArgs()
            ->andReturn(null);
        $response->shouldReceive('body')
            ->andReturn('Internal Server Error');

        // Use reflection to access private method
        $method = new \ReflectionMethod(ScriptMicroserviceRunner::class, 'handleFailedResponse');
        $method->setAccessible(true);

        // Assert exception is thrown with correct message
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Script execution failed: Internal Server Error');

        // Execute the private method
        $method->invoke($this->runner, $response);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
