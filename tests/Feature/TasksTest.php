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
        $response = $this->webGet(self::TASKS_URL, []); //is the path correct?
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index'); // is the view correct? this is set within the Controller
        $response->assertSee('class="container" id="tasks"'); // does the string show within the view?
    }


    public function testShow() 
    {
        // $task = factory(Task::class)->create(['name'=>'Test show']); 
        $response = $this->webGet('tasks/id123');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.show');
        $response->assertSee('Approve?');
    }
}
