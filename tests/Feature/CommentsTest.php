<?php

namespace Tests\Feature;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RequestHelper;

    private function createTask(array $data = [])
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

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        // Get task
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $task = $response->json('data')[0];

        return $task;
    }

    /**
     * @return void
     */
    public function testCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user
        $testUser = User::factory()->create([
            'username' => 'testuser',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }

    /**
     * @return void
     */
    public function testChineseCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user
        $testUser = User::factory()->create([
            'username' => '测试用户',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }

    /**
     * @return void
     */
    public function testArabicCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user
        $testUser = User::factory()->create([
            'username' => 'النسر',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }

    /**
     * @return void
     */
    public function testGermanCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user with german characters
        $testUser = User::factory()->create([
            'username' => 'ÄÖÜäöü',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }

    /**
     * @return void
     */
    public function testArmenianCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user with swiss characters
        $testUser = User::factory()->create([
            'username' => 'օգտագործող',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }

    /**
     * @return void
     */
    public function testBulgarianCommentMentionAreCorrectlyParsedBetweenUserIdAndUsername()
    {
        // Start a process request
        $task = $this->createTask();

        // Create sample user with bulgarian characters
        $testUser = User::factory()->create([
            'username' => 'Тестов',
        ]);

        // Create a comment where the user is tagged
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'body' => 'Should replace the username to user id in mustaches @' . $testUser->username,
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $task['id'],
        ]);

        // Assert that the comment body without the accessor is stored as userid with mustaches
        $this->assertEquals('Should replace the username to user id in mustaches {{' . $testUser->id . '}}', $comment->getRawOriginal('body'));

        // Assert that the comment body with the accessor is parsed to username to the ui
        $this->assertEquals('Should replace the username to user id in mustaches @' . $testUser->username, $comment->body);
    }
}
