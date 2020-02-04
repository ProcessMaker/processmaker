<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessRequestToken;

class CallActivityMultilevelTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests the a process with call activity to a external process definition
     *
     * @group process_tests
     */
    public function testCallActivity()
    {
        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install', ['-vvv' => true]);

        // Create the processes
        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => file_get_contents(__DIR__ . '/processes/multilevel_parent.bpmn')
        ]);
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/multilevel_child1.bpmn')
        ]);
        $grandchild = $this->createProcess([
            'id' => 3,
            'bpmn' => file_get_contents(__DIR__ . '/processes/multilevel_child2.bpmn')
        ]);

        // Start a parent process instance
        $instance = $this->startProcess($parent, 'node_1');

        // Assertion: Active Task = Parent Task
        $activeTask = $instance->tokens()->where('status', 'ACTIVE')->first();
        $this->assertEquals('Parent Task', $activeTask->element_name);
        // Complete Parent Task
        $this->completeTask($activeTask, []);

        // Assertion: Active Task = Child Task
        $childRequest = ProcessRequest::orderBy('id', 'desc')->first();
        $activeTask = $childRequest->tokens()->where('status', 'ACTIVE')->first();
        $this->assertEquals('Child Task', $activeTask->element_name);
        // Complete Child Task
        $this->completeTask($activeTask, []);

        // Assertion: Active Task = Grand Child Task
        $grandChildRequest = ProcessRequest::orderBy('id', 'desc')->first();
        $activeTask = $grandChildRequest->tokens()->where('status', 'ACTIVE')->first();
        $this->assertEquals('Grand Child Task', $activeTask->element_name);
        // Complete Child Task
        $this->completeTask($activeTask, []);

        // Get active tokens
        $instance->refresh();
        $childRequest->refresh();
        $grandChildRequest->refresh();
        $activeTokensParent = $instance->tokens()->where('status', 'ACTIVE')->get();
        $activeTokensChild = $childRequest->tokens()->where('status', 'ACTIVE')->get();
        $activeTokensGrandChild = $grandChildRequest->tokens()->where('status', 'ACTIVE')->get();
        
        // Assertion: All the request were completed
        $this->assertCount(0, $activeTokensParent);
        $this->assertCount(0, $activeTokensChild);
        $this->assertCount(0, $activeTokensGrandChild);
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $childRequest->status);
        $this->assertEquals('COMPLETED', $grandChildRequest->status);
    }
}
