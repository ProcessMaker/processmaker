<?php

namespace Tests;

use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use Tests\TestCase;

class MatchingTasksTest extends TestCase
{
    public function testMatchingInboxRulesForTaskType()
    {
        $user = User::factory()->create();
        $completedTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);
        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => $completedTask->element_id,
            'process_id' => $completedTask->process_id,
        ]);
        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $completedTask->id,
            'user_id' => $user->id,
        ]);

        $matchingTasks = MatchingTasks::matchingInboxRules($activeTask);

        $this->assertCount(1, $matchingTasks);
        $this->assertEquals($inboxRule->id, $matchingTasks[0]->id);
    }

    public function testMatchingInboxRulesForSavedSearch()
    {
        ProcessCategory::factory()->create(['is_system' => true, 'name' => 'System']);

        $user = User::factory()->create();
        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_name' => 'My Test Task',
        ]);
        $savedSearch = SavedSearch::factory()->create([
            'type' => 'task',
            'pmql' => 'task = "My Test Task"',
        ]);
        $inboxRule = InboxRule::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $savedSearch->id,
        ]);
        $nonMatchingSavedSearch = SavedSearch::factory()->create([
            'type' => 'task',
            'pmql' => 'task = "Something Else"',
        ]);
        $nonMatchingInboxRule = InboxRule::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $nonMatchingSavedSearch->id,
        ]);

        $matchingInboxRules = MatchingTasks::matchingInboxRules($activeTask);

        $this->assertCount(1, $matchingInboxRules);
        $this->assertEquals($inboxRule->id, $matchingInboxRules[0]->id);
    }

    public function testInboxRuleEndDate()
    {
        // TODO test if the end date has passed, then the inbox rule should
        // not be returned

        $user = User::factory()->create();
        $completedTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);
        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => $completedTask->element_id,
            'process_id' => $completedTask->process_id,
        ]);
        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $completedTask->id,
            'user_id' => $user->id,
        ]);

        $matchingTasks = MatchingTasks::matchingInboxRules($activeTask);

        $this->assertCount(1, $matchingTasks);
        $this->assertEquals($inboxRule->id, $matchingTasks[0]->id);
    }
}
