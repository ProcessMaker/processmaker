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
        $response->assertSee('class="container" id="tasks"'); 
    }

    public function testShow() 
    { 
        $response = $this->webGet('tasks/id123');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.show');
        $response->assertSee('id="request"');
    }

    public function testEdit()
    {
        $uuid = factory(ProcessRequestToken::class)->create()->uuid_text;
        $response = $this->webGet('tasks/' . $uuid . '/edit');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.edit');
        $response->assertSee('BACK TO TASK LIST');
    }
}
