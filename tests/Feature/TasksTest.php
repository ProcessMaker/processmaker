<?php
namespace Tests\Feature;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use RequestHelper;

    const TASKS_URL = '/tasks';

    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = file_get_contents(__DIR__ . '/Api/processes/ManualTask.bpmn');
        $process = factory(Process::class)->create($data);
        $taskId = 'TaskUID';
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $this->user->id,
            'assignment_type' => User::class,
        ]);
        return $process;
    }

    public function testIndex() {
        $response = $this->webGet(self::TASKS_URL, []); 
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index'); 
        $response->assertSee('Tasks'); 
    }

    public function testViewTaskWithComments() {
        //Start a process request
        $process = $this->createTestProcess();
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $response->assertStatus(201);

        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $task = $response->json('data')[0];

        // A user without permissions for the task should generate a 403 error
        $this->user = factory(User::class)->create();
        $response = $this->webGet(self::TASKS_URL . '/' . $task['id'] . '/edit', []);
        $response->assertStatus(403);

        // Create a comment where the user is not tagged
        factory(Comment::class)->create([
            'user_id' => $this->user->id,
            'body' => 'This comment should not be accessible because @xyz is a non existent user',
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // The user might not be able to access the task view.
        $response = $this->webGet(self::TASKS_URL . '/' . $task['id'] . '/edit', []);
        $response->assertStatus(403);

        // Create a comment where the user is tagged
        factory(Comment::class)->create([
            'user_id' => $this->user->id,
            'body' => 'This comment should be accessible by @' . $this->user->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // The user should be able to access the task view.
        $response = $this->webGet(self::TASKS_URL . '/' . $task['id'] . '/edit', []);
        $response->assertStatus(200);
    }
}
