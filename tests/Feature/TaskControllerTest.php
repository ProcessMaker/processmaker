<?php

namespace Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    public function testShowScreen()
    {
        $process = $this->createProcess([
            'id' => 1,
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/rollback_test.bpmn'),
        ]);

        $instance = $this->startProcess($parent, 'node_1');
        $activeTask = $instance->tokens()->where('status', 'ACTIVE')->first();

        dd($activeTask);
        $processAbeRequest = ProcessAbeRequestToken::factory()->create();

        $response = $this->webCall('GET', 'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes');

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }
}
