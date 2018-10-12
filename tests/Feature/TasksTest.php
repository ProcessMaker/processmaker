<?php
namespace Tests\Feature;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use RequestHelper;

    const TASKS_URL = '/tasks';

    protected $structure = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    public function testIndex() {
        $response = $this->webGet(self::TASKS_URL, []); //is the path correct?
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index'); // is the view correct? this is set within the Controller
        $response->assertSee('class="container" id="tasks"'); // does the string show within the view?
    }
}
