<?php

namespace Tests;

use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
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
    
        $task = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);

        //Check with pastEndDate value
        $pastEndDate = now()->subDays(1);
        InboxRule::factory()->create([
            'process_request_token_id' => $task->id,
            'end_date' => $pastEndDate,
            'user_id' => $user->id,
        ]);

        $matchingRules = MatchingTasks::matchingInboxRules($task);
        $this->assertEmpty($matchingRules);

        //Check with futureEndDate value
        $futureEndDate = now()->addDays(1);
        InboxRule::factory()->create([
            'process_request_token_id' => $task->id,
            'end_date' => $futureEndDate,
            'user_id' => $user->id,
        ]);

        $matchingRules = MatchingTasks::matchingInboxRules($task);

        $this->assertNotEmpty($matchingRules);
    }

    public function testGet()
    {
        $user = User::factory()->create();
    
        $task = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);

        ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'process_id' => $task->process_id,
            'element_id' => $task->element_id,
        ]);

        $inboxRule = InboxRule::factory()->create([
            'process_request_token_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $matchingTasks = MatchingTasks::get($inboxRule);

        $this->assertEquals($task->process_id, $matchingTasks[0]["process_id"]);
    }
}
