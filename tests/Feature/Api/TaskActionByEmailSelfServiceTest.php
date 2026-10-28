<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskActionByEmailSelfServiceTest extends TestCase
{
    use RequestHelper;

    public function testActionByEmailCreatesTokenWhenSelfServiceWithGroupAssignment(): void
    {
        $directUser = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'abe-direct@example.com',
        ]);
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        $groupMember = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'abe-group-member@example.com',
        ]);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $groupMember->id,
            'member_type' => User::class,
        ]);

        $bpmn = str_replace(
            ['[ASSIGNED_USERS]', '[ASSIGNED_GROUPS]'],
            [(string) $directUser->id, (string) $group->id],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);

        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $directUser->id,
            'assignment_type' => User::class,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->firstOrFail();
        $this->assertEquals(1, $task->is_self_service);
        $this->assertNull($task->user_id);

        $abeToken = ProcessAbeRequestToken::first();
        $this->assertNotNull($abeToken);
        $this->assertEquals($task->id, $abeToken->process_request_token_id);
    }
}
