<?php

namespace Tests\Feature;

use ProcessMaker\Models\ProcessAbeRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    public function testShowScreen()
    {
        $processAbeRequestUuid = ProcessAbeRequestToken::factory()->create();
        $response = $this->webCall('GET', 'tasks/update_variable/' . $processAbeRequestUuid->uuid);

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }
}
