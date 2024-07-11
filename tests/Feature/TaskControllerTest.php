<?php

namespace Tests\Feature;

use ProcessMaker\Models\Screen;
use ProcessMaker\Http\Controllers\TaskController;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{

    public function testShowScreen()
    {
        $screenId = Screen::factory()->create()->id;
        $response = (new TaskController)->showScreen($screenId);

        $response->assertViewIs('processes.screens.completedScreen');
    }
}
