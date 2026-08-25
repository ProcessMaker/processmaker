<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunNayraScriptTask;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Managers\WorkflowManagerDefault;
use Tests\TestCase;

class RunScriptTaskTest extends TestCase
{
    public function testRealtimeScriptTaskUsesRealtimeQueue()
    {
        Queue::fake();
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);
        $executor = ScriptExecutor::factory()->create([
            'language' => 'php',
            'type' => ScriptExecutorType::Realtime,
        ]);
        $script = Script::factory()->create([
            'script_executor_id' => $executor->id,
        ]);
        $scriptTask = Mockery::mock(ScriptTaskInterface::class);
        $scriptTask->shouldReceive('getId')->once()->andReturn('script-task');
        $scriptTask->shouldReceive('getProperty')
            ->once()
            ->with('scriptRef')
            ->andReturn($script->id);

        (new WorkflowManagerDefault())->runScripTask($scriptTask, $token);

        Queue::assertPushed(RunScriptTask::class, function (RunScriptTask $job) {
            return $job->connection === 'redis-realtime' && $job->queue === 'realtime';
        });
    }

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

    public static function jobTypes()
    {
        return [
            [RunScriptTask::class],
            [RunNayraScriptTask::class],
        ];
    }
}
