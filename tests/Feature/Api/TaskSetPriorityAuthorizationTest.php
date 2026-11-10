<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * PUT /api/1.0/tasks/{task}/setPriority must authorize the same task it updates.
 *
 * Callers who pass ProcessRequestTokenPolicy::update (assignee, process manager,
 * self-service candidate, administrator, or any user when the task is assigned to
 * the anonymous user) may change is_priority. Everyone else receives 403 and the
 * task row is left untouched, including when the caller may update a different task.
 */
class TaskSetPriorityAuthorizationTest extends TestCase
{
    use RequestHelper;

    public function testGuestCannotChangeTaskPriority(): void
    {
        $owner = User::factory()->create();
        $task = $this->makeTask($owner, ['is_priority' => false]);
        $before = $this->snapshot($task);

        $response = $this->json('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertUnauthorized();
        $this->assertTaskUnchanged($task, $before);
    }

    public function testStrangerCannotFlagAnotherUsersTask(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask($owner, ['is_priority' => false]);
        $before = $this->snapshot($task);

        $this->user = $stranger;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);
        $this->assertFalse($task->fresh()->is_priority);
    }

    public function testStrangerCannotUnflagAnotherUsersTask(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask($owner, ['is_priority' => true]);
        $before = $this->snapshot($task);

        $this->user = $stranger;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => false,
        ]);

        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testStrangerCannotClearPriorityWhenTheFlagIsOmitted(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask($owner, ['is_priority' => true]);
        $before = $this->snapshot($task);

        $this->user = $stranger;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), []);

        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testCallerWhoMayUpdateADifferentTaskCannotChangeTheRequestedTask(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(['is_administrator' => false]);
        $victim = $this->makeTask($owner, ['is_priority' => false]);
        $ownTask = $this->makeTask($attacker, ['is_priority' => false]);
        $victimBefore = $this->snapshot($victim);
        $ownBefore = $this->snapshot($ownTask);

        $this->user = $attacker;
        $response = $this->apiCall('PUT', $this->priorityUrl($victim), [
            'is_priority' => true,
        ]);

        $response->assertForbidden();
        $this->assertTaskUnchanged($victim, $victimBefore);
        $this->assertTaskUnchanged($ownTask, $ownBefore);
        $this->assertFalse($victim->fresh()->is_priority);
        $this->assertFalse($ownTask->fresh()->is_priority);
    }

    public function testManagerOfAnotherProcessCannotChangeThisTaskPriority(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create(['is_administrator' => false]);
        $otherProcess = Process::factory()->create();
        $otherProcess->manager_id = [$manager->id];
        $otherProcess->save();

        $task = $this->makeTask($owner, ['is_priority' => false]);
        $before = $this->snapshot($task);

        $this->user = $manager;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);
    }

    public function testProcessRequesterWhoIsNotTheAssigneeCannotChangeTaskPriority(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create(['is_administrator' => false]);
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $requester->id,
        ]);
        $task = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'is_priority' => false,
            'is_self_service' => false,
        ]);
        $before = $this->snapshot($task);

        $this->user = $requester;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);
    }

    public function testAssigneeCanFlagAndUnflagOwnTask(): void
    {
        $owner = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask($owner, ['is_priority' => false]);

        $this->user = $owner;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('is_priority', true);
        $this->assertTrue($task->fresh()->is_priority);
        $this->assertSame($owner->id, $task->fresh()->user_id);

        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('is_priority', false);
        $this->assertFalse($task->fresh()->is_priority);
    }

    public function testAdministratorCanChangeAnotherUsersTaskPriority(): void
    {
        $owner = User::factory()->create(['is_administrator' => false]);
        $admin = User::factory()->create(['is_administrator' => true]);
        $task = $this->makeTask($owner, ['is_priority' => false]);

        $this->user = $admin;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
        $this->assertSame($owner->id, $task->fresh()->user_id);
    }

    public function testProcessManagerCanChangeTaskPriority(): void
    {
        $owner = User::factory()->create(['is_administrator' => false]);
        $manager = User::factory()->create(['is_administrator' => false]);
        $process = Process::factory()->create();
        $process->manager_id = [$manager->id];
        $process->save();
        $task = $this->makeTask($owner, ['is_priority' => false], $process);

        $this->user = $manager;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testListedSelfServiceUserCanChangeTaskPriority(): void
    {
        $eligible = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask(User::factory()->create(), [
            'user_id' => null,
            'is_priority' => false,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [$eligible->id],
                'groups' => [],
            ],
        ]);

        $this->user = $eligible;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testSelfServiceGroupMemberCanChangeTaskPriority(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create(['is_administrator' => false]);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'member_type' => User::class,
        ]);
        $task = $this->makeTask(User::factory()->create(), [
            'user_id' => null,
            'is_priority' => false,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [],
                'groups' => [$group->id],
            ],
        ]);

        $this->user = $member;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testSelfServiceUserCanChangePriorityAndAnOutsiderCannot(): void
    {
        $eligible = User::factory()->create(['is_administrator' => false]);
        $outsider = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask(User::factory()->create(), [
            'user_id' => null,
            'is_priority' => false,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [$eligible->id],
                'groups' => [],
            ],
        ]);
        $before = $this->snapshot($task);

        $this->user = $outsider;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);
        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);

        $this->user = $eligible;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);
        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testSelfServiceGroupMemberCanChangePriorityAndANonMemberCannot(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create(['is_administrator' => false]);
        $nonMember = User::factory()->create(['is_administrator' => false]);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'member_type' => User::class,
        ]);

        $task = $this->makeTask(User::factory()->create(), [
            'user_id' => null,
            'is_priority' => false,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [],
                'groups' => [$group->id],
            ],
        ]);
        $before = $this->snapshot($task);

        $this->user = $nonMember;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);
        $response->assertForbidden();
        $this->assertTaskUnchanged($task, $before);

        $this->user = $member;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);
        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
    }

    public function testAnyActiveUserCanChangePriorityOfATaskAssignedToTheAnonymousUser(): void
    {
        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $stranger = User::factory()->create(['is_administrator' => false]);
        $task = $this->makeTask($anonymous, ['is_priority' => false]);

        $this->user = $stranger;
        $response = $this->apiCall('PUT', $this->priorityUrl($task), [
            'is_priority' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($task->fresh()->is_priority);
        $this->assertSame($anonymous->id, $task->fresh()->user_id);
    }

    private function makeTask(User $assignee, array $attributes = [], ?Process $process = null): ProcessRequestToken
    {
        $process ??= Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        return ProcessRequestToken::factory()->create(array_merge([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $assignee->id,
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'is_priority' => false,
            'is_self_service' => false,
        ], $attributes));
    }

    private function priorityUrl(ProcessRequestToken $task): string
    {
        return '/api/1.0/tasks/' . $task->id . '/setPriority';
    }

    /**
     * @return array{is_priority: bool, user_id: mixed, status: string, updated_at: string, version_id: mixed, screen_count: int}
     */
    private function snapshot(ProcessRequestToken $task): array
    {
        $fresh = $task->fresh();

        return [
            'is_priority' => (bool) $fresh->is_priority,
            'user_id' => $fresh->user_id,
            'status' => $fresh->status,
            'updated_at' => $fresh->updated_at?->toJSON(),
            'version_id' => $fresh->version_id,
            'screen_count' => Screen::count(),
        ];
    }

    private function assertTaskUnchanged(ProcessRequestToken $task, array $before): void
    {
        $fresh = $task->fresh();
        $this->assertSame($before['is_priority'], (bool) $fresh->is_priority);
        $this->assertSame($before['user_id'], $fresh->user_id);
        $this->assertSame($before['status'], $fresh->status);
        $this->assertSame($before['updated_at'], $fresh->updated_at?->toJSON());
        $this->assertSame($before['version_id'], $fresh->version_id);
        $this->assertSame($before['screen_count'], Screen::count());
    }
}
