<?php

namespace Tests;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ApplyActionTest extends TestCase
{
    public function testCompleteTaskWithData()
    {
        $user = User::factory()->create();

        $taskForInboxRule = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $taskForInboxRule->id,
            'user_id' => $user->id,
            'submit_data' => true,
            'data' => ['foo' => 'bar'],
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
        ]);

        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(
                $activeTask->process,
                $activeTask->processRequest,
                $activeTask,
                ['foo' => 'bar']
            );

        ApplyAction::applyActionOnTask($activeTask, $inboxRule);

        $inboxRuleLog = InboxRuleLog::orderBy('id', 'desc')->first();
        $this->assertEquals($inboxRuleLog->inbox_rule_attributes, $inboxRule->getAttributes());
        $this->assertEquals($inboxRuleLog->process_request_token_id, $activeTask->id);
        $this->assertEquals($inboxRuleLog->inbox_rule_id, $inboxRule->id);
    }

    public function testMarkAsPriority()
    {
        $user = User::factory()->create();

        $taskForInboxRule = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $taskForInboxRule->id,
            'user_id' => $user->id,
            'mark_as_priority' => true,
            'submit_data' => false,
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
            'is_priority' => false,
        ]);

        ApplyAction::applyActionOnTask($activeTask, $inboxRule);

        $activeTask->refresh();

        $this->assertTrue($activeTask->is_priority);
    }

    public function testReassignToUserID()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
            'status' => 'ACTIVE',
        ]);

        $userToReassign = User::factory()->create();

        $taskForInboxRule = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $taskForInboxRule->id,
            'user_id' => $user->id,
            'reassign_to_user_id' => $userToReassign->id,
            'submit_data' => false,
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
        ]);

        // Test that the reassign() method is called with the correct users
        $activeTask = \Mockery::mock($activeTask)
            ->shouldReceive('reassign')
            ->once()
            ->with($userToReassign->id, \Mockery::on(fn ($arg) => $arg->id === $user->id))
            ->getMock();

        ApplyAction::applyActionOnTask($activeTask, $inboxRule);
    }

    public function testSaveAsDraft()
    {
        $user = User::factory()->create();

        $taskForInboxRule = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $taskForInboxRule->id,
            'user_id' => $user->id,
            'reassign_to_user_id' => null,
            'submit_data' => false,
            'make_draft' => true,
            'data' => ['input' => 'some rule text'],
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
            'data' => ['input' => 'active value'],
        ]);

        ApplyAction::applyActionOnTask($activeTask, $inboxRule);

        $taskDraftData = TaskDraft::where('task_id', $activeTask->id)->value('data');

        // Check if the expected data is contained in the recovered data
        $this->assertArraySubset($inboxRule->data, $taskDraftData);
    }
}
