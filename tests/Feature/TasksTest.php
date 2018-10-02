<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use DatabaseTransactions;
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
        $response->assertSee('Approve?');
    }
}
