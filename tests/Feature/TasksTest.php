<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use RequestHelper;

    const TASKS_URL = '/tasks';

    public function testIndex() {
        $response = $this->webGet(self::TASKS_URL, []); 
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index'); 
        $response->assertSee('Tasks'); 
    }

}
