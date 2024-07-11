<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskActionByEmailTest extends TestCase
{
    use RequestHelper;

    /**
     * Create new task assignment type user successfully
     */
    private function loadTestProcessUserAssignment($file)
    {
        // Create a new process
        $this->process = Process::factory()->create();
        $this->process->bpmn = $file;
        $this->process->save();
    }

    /**
     * Execute a process with an empty Action by Email
     */
    public function testProcessWithEmptyActionByEmail()
    {
        $this->loadTestProcessUserAssignment(file_get_contents(__DIR__ . '/processes/TaskWithActionByEmailEmpty.bpmn'));

        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        //Verify status
        $response->assertStatus(201);
    }

    /**
     * Execute a process with an Action by Email
     */
    public function testProcessWithActionByEmail()
    {
        $this->loadTestProcessUserAssignment(file_get_contents(__DIR__ . '/processes/TaskWithActionByEmail.bpmn'));

        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        //Verify status
        $response->assertStatus(201);
    }
}
