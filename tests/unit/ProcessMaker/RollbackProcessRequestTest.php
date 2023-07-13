<?php

namespace Tests;

use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Repositories\BpmnDocument;

class RollbackProcessRequestTest extends TestCase
{
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

        $mockProcessDefinitions = Mockery::mock(BpmnDocument::class);
        $newTask = RollbackProcessRequest::rollback($task3, $mockProcessDefinitions);
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

        $mockProcessDefinitions = $this->mockRunScriptTask();

        $newTask = RollbackProcessRequest::rollback($task3, $mockProcessDefinitions);
        $this->assertEquals('node_5', $newTask->element_id);
        $this->assertEquals('ACTIVE', $newTask->status);
    }

    public function testRollbackToServiceTask()
    {
        $processRequest = ProcessRequest::factory()->create(['status' => 'ERROR']);
        $task1 = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'element_id' => 'node_5',
            'element_type' => 'serviceTask',
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

        $mockProcessDefinitions = $this->mockRunServiceTask();

        $newTask = RollbackProcessRequest::rollback($task3, $mockProcessDefinitions);
        $this->assertEquals('node_5', $newTask->element_id);
        $this->assertEquals('ACTIVE', $newTask->status);
        $this->assertEquals('ACTIVE', $processRequest->refresh()->status);
    }

    private function mockRunScriptTask()
    {
        $mocksScriptTask = Mockery::mock(ScriptTaskInterface::class);
        $mockProcessDefinitions = Mockery::mock(BpmnDocument::class);
        $mockProcessDefinitions->shouldReceive('getEvent')
            ->with('node_5')
            ->andReturn($mocksScriptTask);
        WorkflowManager::shouldReceive('runScripTask')
            ->withArgs(function ($scriptTask, $task) use ($mocksScriptTask) {
                return $scriptTask === $mocksScriptTask && $task->element_id = 'node_5';
            });

        return $mockProcessDefinitions;
    }

    private function mockRunServiceTask()
    {
        $mockServiceTask = Mockery::mock(ServiceTaskInterface::class);
        $mockProcessDefinitions = Mockery::mock(BpmnDocument::class);
        $mockProcessDefinitions->shouldReceive('getEvent')
            ->with('node_5')
            ->andReturn($mockServiceTask);
        WorkflowManager::shouldReceive('runServiceTask')
            ->withArgs(function ($serviceTask, $task) use ($mockServiceTask) {
                return $serviceTask === $mockServiceTask && $task->element_id = 'node_5';
            });

        return $mockProcessDefinitions;
    }
}
