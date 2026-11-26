<?php

namespace Tests\Jobs;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\RunNayraScriptTask;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\ScriptRunners\MockRunner;
use Tests\TestCase;

class RunScriptTaskTest extends TestCase
{
    #[DataProvider('jobTypes')]
    public function testScriptNotSet($class)
    {
        $request = $this->runJob($class, '');

        $this->assertEmpty($request->errors);
        $this->assertEquals('my node (node_2): No code or script assigned to "Script Task"', $request->data['_configuration_error_node_2']);
    }

    #[DataProvider('jobTypes')]
    public function testScriptNotFound($class)
    {
        $request = $this->runJob($class, 12345);

        $this->assertEmpty($request->errors);
        $this->assertEquals('my node (node_2): Script "12345" not found', $request->data['_configuration_error_node_2']);
    }

    #[DataProvider('jobTypes')]
    public function testRunAsUserNotFound($class)
    {
        $script = Script::factory()->create(['run_as_user_id' => null]);
        $request = $this->runJob($class, $script->id);

        $this->assertEmpty($request->errors);
        $this->assertEquals('my node (node_2): A user is required to run scripts', $request->data['_configuration_error_node_2']);
    }

    public function testRequestExceptionMarksTokenFailingAndStoresError(): void
    {
        $this->mockScriptRunnerThrowingRequestException(503);

        $request = $this->startScriptWithErrorHandling([
            'retry_attempts' => 0,
            'retry_wait_time' => 1,
        ]);

        $token = $request->tokens()->where('element_id', 'node_54')->first();

        $this->assertNotNull($token);
        $this->assertEquals(ScriptTaskInterface::TOKEN_STATE_FAILING, $token->status);
        $this->assertNotEmpty($request->refresh()->errors);
        $this->assertStringContainsString('HTTP request returned status code 503', $request->errors[0]['message']);
    }

    public function testRequestExceptionUsesConfiguredRetries(): void
    {
        Queue::fake();
        $this->mockScriptRunnerThrowingRequestException(503);

        $user = User::factory()->create();
        Auth::login($user);

        $script = Script::factory()->create([
            'retry_attempts' => 0,
            'retry_wait_time' => 1,
        ]);
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/ScriptWithErrorHandling.bpmn');
        $bpmn = str_replace('[script_id]', $script->id, $bpmn);
        $errorHandlingValue = str_replace('"', '&#34;', json_encode([
            'retry_attempts' => 2,
            'retry_wait_time' => 5,
        ]));
        $bpmn = str_replace('[error_handling]', $errorHandlingValue, $bpmn);

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
        ]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_id' => 'node_54',
            'element_type' => 'scriptTask',
            'element_name' => 'Script Task',
            'status' => 'ACTIVE',
        ]);

        (new RunScriptTask($process, $request, $token, [], 1))->handle();

        Queue::assertPushed(RunScriptTask::class, function ($job) {
            return $job->attemptNum === 2 && $job->delay === 5;
        });

        $this->assertNotEquals(
            ScriptTaskInterface::TOKEN_STATE_FAILING,
            $token->refresh()->status
        );
    }

    private function runJob($class, $scriptId)
    {
        $user = User::factory()->create();
        Auth::login($user);
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/script_without_settings.bpmn');
        $bpmn = str_replace('[script_id]', $scriptId, $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        $process->manager_id = $user->id;
        $process->save();

        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_id' => 'node_2',
            'element_name' => 'my node',
            'status' => 'ACTIVE',
        ]);

        if ($class === RunScriptTask::class) {
            $class::dispatch($process, $request, $token, []);
        } else {
            $class::dispatch($token);
        }

        return $request->refresh();
    }

    private function startScriptWithErrorHandling(array $errorHandling): ProcessRequest
    {
        $user = User::factory()->create();
        Auth::login($user);

        $script = Script::factory()->create([]);
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/ScriptWithErrorHandling.bpmn');
        $bpmn = str_replace('[script_id]', $script->id, $bpmn);
        $errorHandlingValue = str_replace('"', '&#34;', json_encode($errorHandling));
        $bpmn = str_replace('[error_handling]', $errorHandlingValue, $bpmn);

        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'properties' => [
                'manager_id' => $user->id,
            ],
        ]);
        $event = $process->getDefinitions()->getEvent('node_45');

        return WorkflowManager::triggerStartEvent($process, $event, []);
    }

    private function mockScriptRunnerThrowingRequestException(int $status = 503): void
    {
        $mock = Mockery::mock(MockRunner::class);
        $mock->shouldReceive('setTokenId');
        $mock->shouldReceive('run')->andReturnUsing(function () use ($status) {
            $response = new Response(
                new \GuzzleHttp\Psr7\Response($status, [], 'Service Unavailable')
            );

            throw new RequestException($response);
        });

        app()->bind(MockRunner::class, function () use ($mock) {
            return $mock;
        });
    }

    public static function jobTypes()
    {
        return [
            [RunScriptTask::class],
            [RunNayraScriptTask::class],
        ];
    }
}
