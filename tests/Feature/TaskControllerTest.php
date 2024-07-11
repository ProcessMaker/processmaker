<?php

namespace Tests\Feature;

use ProcessMaker\Models\Screen;
use ProcessMaker\Http\Controllers\TaskController;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    public function testShowScreen()
    {
        $screenId = Screen::factory()->create()->id;
        $response = (new TaskController)->showScreen($screenId);

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
    }
}
