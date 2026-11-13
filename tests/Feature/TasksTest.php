<?php

namespace Tests\Feature;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserConfiguration;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TasksTest extends TestCase
{
    use RequestHelper;

    const TASKS_URL = '/tasks';

    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = file_get_contents(__DIR__ . '/Api/processes/ManualTask.bpmn');
        $process = Process::factory()->create($data);
        $taskId = 'TaskUID';
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $this->user->id,
            'assignment_type' => User::class,
        ]);

        return $process;
    }

    public function testIndex()
    {
        $response = $this->webGet(self::TASKS_URL, []);
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index');
        $response->assertSee('Tasks');
    }

    public function testTasksPageSendsPrivateEtagHeaders()
    {
        $response = $this->webGet(self::TASKS_URL, []);

        $response->assertStatus(200);
        $response->assertHeader('ETag');
        $this->assertCacheControlHasPrivateMustRevalidate($response);
        $response->assertHeaderMissing('Pragma');
        $response->assertHeaderMissing('Expires');
    }

    public function testTasksPageReturnsNotModifiedWhenEtagMatches()
    {
        $response = $this->webGet(self::TASKS_URL, []);
        $etag = $response->headers->get('ETag');

        $responseWithMatchingEtag = $this->actingAs($this->user, 'web')
            ->withHeaders(['If-None-Match' => $etag])
            ->get(self::TASKS_URL);

        $responseWithMatchingEtag->assertStatus(304);
        $this->assertEquals(
            $this->stripWeakEtagPrefix($etag),
            $this->stripWeakEtagPrefix($responseWithMatchingEtag->headers->get('ETag'))
        );
        $this->assertCacheControlHasPrivateMustRevalidate($responseWithMatchingEtag);
        $this->assertEmpty($responseWithMatchingEtag->getContent());
    }

    public function testTasksPageEtagChangesWhenUserConfigurationChanges()
    {
        $response = $this->webGet(self::TASKS_URL, []);
        $etag = $response->headers->get('ETag');

        UserConfiguration::create([
            'user_id' => $this->user->id,
            'ui_configuration' => json_encode([
                'tasks' => [
                    'isMenuCollapse' => false,
                ],
            ]),
        ]);

        $updatedResponse = $this->webGet(self::TASKS_URL, []);

        $this->assertNotEquals($etag, $updatedResponse->headers->get('ETag'));
    }

    public function testTasksPageEtagChangesWhenFeatureConfigChanges()
    {
        $response = $this->webGet(self::TASKS_URL, []);
        $etag = $response->headers->get('ETag');

        config()->set('app.task_drafts_enabled', !config('app.task_drafts_enabled'));

        $updatedResponse = $this->webGet(self::TASKS_URL, []);

        $this->assertNotEquals($etag, $updatedResponse->headers->get('ETag'));
    }

    private function assertCacheControlHasPrivateMustRevalidate($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    private function stripWeakEtagPrefix(?string $etag): ?string
    {
        return $etag ? str_replace('W/', '', $etag) : null;
    }

    public function testViewTaskWithComments()
    {
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
        $this->user = User::factory()->create([
            'username' => 'testuser',
        ]);
        $response = $this->webGet(self::TASKS_URL . '/' . $task['id'] . '/edit', []);
        $response->assertStatus(403);

        // Create a comment where the user is not tagged
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'This comment should not be accessible because @xyz is a non existent user',
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // The user might not be able to access the task view.
        $response = $this->webGet(self::TASKS_URL . '/' . $task['id'] . '/edit', []);
        $response->assertStatus(403);

        // Create a comment where the user is tagged
        Comment::factory()->create([
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
