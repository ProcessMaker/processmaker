<?php

namespace Tests;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Client\Model\ProcessCategory as ModelProcessCategory;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
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
            'submit_data' => ['foo' => 'bar'],
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
        ]);

        MatchingTasks::shouldReceive('matchingInboxRules')
            ->once()
            ->with($activeTask)
            ->andReturn([$inboxRule]);

        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(
                $activeTask->process,
                $activeTask->processRequest,
                $activeTask,
                ['foo' => 'bar']
            );

        ApplyAction::applyActionOnTask($activeTask);
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
            'submit_data' => null,
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
            'is_priority' => false,
        ]);

        MatchingTasks::shouldReceive('matchingInboxRules')
            ->once()
            ->with($activeTask)
            ->andReturn([$inboxRule]);

        ApplyAction::applyActionOnTask($activeTask);

        $activeTask->refresh();

        $this->assertTrue($activeTask->is_priority);
    }

    public function testReassignToUserID()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
            'status' => 'ACTIVE'
        ]);
        $this->actingAs($user);

        $userToReassign = User::factory()->create();

        $taskForInboxRule = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $taskForInboxRule->id,
            'user_id' => $user->id,
            'reassign_to_user_id' => $userToReassign->id,
            'submit_data' => null,
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
        ]);

        MatchingTasks::shouldReceive('matchingInboxRules')
            ->once()
            ->with($activeTask)
            ->andReturn([$inboxRule]);

        ApplyAction::applyActionOnTask($activeTask);
        
        $activeTask->refresh();
        
        $this->assertEquals($userToReassign->id,$activeTask->user_id);
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
            'submit_data' => null,
            'fill_data' => true,
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
            'data' => ['input' => 'draft value'],
        ]);

        MatchingTasks::shouldReceive('matchingInboxRules')
            ->once()
            ->with($activeTask)
            ->andReturn([$inboxRule]);

        ApplyAction::applyActionOnTask($activeTask);

        $taskDraftData = TaskDraft::where('task_id', $activeTask->id)->value('data');

        // Check if the expected data is contained in the recovered data
        $this->assertArraySubset($activeTask->data, $taskDraftData);
    }
}
