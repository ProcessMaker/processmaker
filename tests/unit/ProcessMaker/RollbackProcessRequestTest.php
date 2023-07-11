<?php

namespace Tests;

use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\ImportExport\Psudomodels\Psudomodel;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\EventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Repositories\BpmnDocument;

class RollbackProcessRequestTest extends TestCase
{
    private $process;

    private $processRequest;

    private $user;

    public function setUpProcessRequest()
    {
        return;

        $this->user = User::factory()->create();
        Auth::login($this->user);

        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/rollback_test.bpmn');

        $workingScript = Script::factory()->create(['code' => '<?php return ["success" => 1]; ']);
        $workingServiceTask = Script::factory()->create([
            'key' => 'test/foo',
            'code' => '<?php return ["success" => 1]; ',
        ]);
        $errorScript = Script::factory()->create(['code' => '<?php throw new \Exception("an error");']);
        $bpmn = str_replace('[working_script_id]', $workingScript->id, $bpmn);
        $bpmn = str_replace('[error_script_id]', $errorScript->id, $bpmn);

        $this->process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        $this->processRequest = ProcessRequest::factory()->create([
            'process_id' => $this->process->id,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $this->process->id,
            'process_task_id' => 'node_45',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'user',
        ]);
    }

    // public function testRollbackToFormTask()
    // {
    //     $definitions = $this->process->getDefinitions();
    //     $startEvent = $definitions->getEvent('node_1');
    //     $request = WorkflowManager::triggerStartEvent($this->process, $startEvent, []);
    //     $workingFormTask = $request->tokens()->where('element_id', 'node_45')->first();
    //     WorkflowManager::completeTask($this->process, $request, $workingFormTask, []);

    //     $failedTask = $request->tokens()->where('element_id', 'node_145')->first();
    //     $newTask = RollbackProcessRequest::rollback($failedTask);

    //     $this->assertEquals('node_45', $newTask->element_id);
    //     $this->assertEquals('ACTIVE', $newTask->status);
    // }

    // public function testRollbackToScriptTask()
    // {
    //     $definitions = $this->process->getDefinitions();
    //     $startEvent = $definitions->getEvent('node_1');
    //     $request = WorkflowManager::triggerStartEvent($this->process, $startEvent, []);

    //     $failedTask = $request->tokens()->where('element_id', 'node_145')->orderBy('id', 'desc')->first();
    //     $newTask = RollbackProcessRequest::rollback($failedTask);

    //     $this->assertEquals('node_2', $newTask->element_id);
    //     $this->assertEquals('ACTIVE', $newTask->status);
    // }

    public function testRollbackToFormTask()
    {
        $processRequest = ProcessRequest::factory()->create(['status' => 'ERROR']);
        $task1 = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_5',
            'element_type' => 'task',
        ]);
        $task2 = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_6',
            'element_type' => 'gateway',
        ]);
        $task3 = ProcessRequestToken::factory()->create([
            'status' => 'FAILING',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_7',
            'element_type' => 'scriptTask',
        ]);

        $newTask = RollbackProcessRequest::rollback($task3);
        $this->assertEquals('node_5', $newTask->element_id);
        $this->assertEquals('ACTIVE', $newTask->status);

        $comment = Comment::orderBy('id', 'desc')->first();
        $this->assertEquals($comment->body, "The System rolled back {$task3->element_name} to {$newTask->element_name}");
    }

    public function testRollbackToScriptTask()
    {
        $processRequest = ProcessRequest::factory()->create(['status' => 'ERROR']);
        $task1 = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_5',
            'element_type' => 'scriptTask',
        ]);
        $task2 = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_6',
            'element_type' => 'gateway',
        ]);
        $task3 = ProcessRequestToken::factory()->create([
            'status' => 'FAILING',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_7',
            'element_type' => 'scriptTask',
        ]);

        $mocksScriptTask = Mockery::mock(ScriptTaskInterface::class);
        $mockProcessDefinitions = Mockery::mock(BpmnDocument::class);
        $mockProcessDefinitions->shouldReceive('getEvent')
            ->with('node_5')
            ->andReturn($mocksScriptTask);
        WorkflowManager::shouldReceive('runScripTask')
            ->withArgs(function ($scriptTask, $task) use ($mocksScriptTask) {
                return $scriptTask === $mocksScriptTask && $task->element_id = 'node_5';
            });

        $newTask = RollbackProcessRequest::rollback($task3, $mockProcessDefinitions);
        $this->assertEquals('node_5', $newTask->element_id);
        $this->assertEquals('ACTIVE', $newTask->status);
    }
}
