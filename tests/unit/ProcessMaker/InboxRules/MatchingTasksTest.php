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
        $this->markTestSkipped('We are not longer matching task type in inbox rules. Must use saved search.');

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

        // Non-matching inbox rule
        InboxRule::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $nonMatchingSavedSearch->id,
        ]);

        $matchingInboxRules = MatchingTasks::matchingInboxRules($activeTask);

        $this->assertCount(1, $matchingInboxRules);
        $this->assertEquals($inboxRule->id, $matchingInboxRules[0]->id);
    }

    public function testMatchingInboxRulesForAdvancedFilter()
    {
        ProcessCategory::factory()->create(['is_system' => true, 'name' => 'System']);

        $user = User::factory()->create();

        $processRequest = ProcessRequest::factory()->create([
            'data' => [
                'some_variable' => 'some value',
            ],
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'process_request_id' => $processRequest->id,
        ]);

        $advancedFilter = [
            'order' => ['by' => 'id', 'direction' => 'desc'],
            'filters' => [
                [
                    'subject' => ['type' => 'Field', 'value' => 'data.some_variable'],
                    'operator' => '=',
                    'value' => 'some value',
                ], [
                    'subject' => ['type' => 'Field', 'value' => 'user_id'],
                    'operator' => '=',
                    'value' => $user->id,
                ],
            ],
        ];

        $savedSearch = SavedSearch::factory()->create([
            'type' => 'task',
            'advanced_filter' => $advancedFilter,
        ]);

        $inboxRule = InboxRule::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $savedSearch->id,
        ]);

        // Non-matching saved search
        $filterCopy = $advancedFilter;
        $filterCopy['filters'][0]['value'] = 'some other value';
        $nonMatchingSavedSearch = SavedSearch::factory()->create([
            'type' => 'task',
            'advanced_filter' => $filterCopy,
        ]);

        // Non-matching inbox rule
        InboxRule::factory()->create([
            'user_id' => $user->id,
            'saved_search_id' => $nonMatchingSavedSearch->id,
        ]);

        $matchingInboxRules = MatchingTasks::matchingInboxRules($activeTask);

        $this->assertCount(1, $matchingInboxRules);
        $this->assertEquals($inboxRule->id, $matchingInboxRules[0]->id);
    }

    public function testInboxRuleEndDate()
    {
        ProcessCategory::factory()->create(['is_system' => true, 'name' => 'System']);

        $user = User::factory()->create();

        $task = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);

        $advancedFilter = [
            'order' => ['by' => 'id', 'direction' => 'desc'],
            'filters' => [
                [
                    'subject' => ['type' => 'Field', 'value' => 'process_id'],
                    'operator' => '=',
                    'value' => $task->process_id,
                ], [
                    'subject' => ['type' => 'Field', 'value' => 'element_id'],
                    'operator' => '=',
                    'value' => $task->element_id,
                ], [
                    'subject' => ['type' => 'Field', 'value' => 'user_id'],
                    'operator' => '=',
                    'value' => $user->id,
                ],
            ],
        ];

        $savedSearch = SavedSearch::factory()->create([
            'type' => 'task',
            'advanced_filter' => $advancedFilter,
        ]);

        //Check with pastEndDate value

        $pastEndDate = now()->subDays(1);
        InboxRule::factory()->create([
            'saved_search_id' => $savedSearch->id,
            'end_date' => $pastEndDate,
            'user_id' => $user->id,
        ]);

        $matchingRules = MatchingTasks::matchingInboxRules($task);
        $this->assertEmpty($matchingRules);

        //Check with futureEndDate value
        $futureEndDate = now()->addDays(1);
        InboxRule::factory()->create([
            'saved_search_id' => $savedSearch->id,
            'end_date' => $futureEndDate,
            'user_id' => $user->id,
        ]);

        $matchingRules = MatchingTasks::matchingInboxRules($task);

        $this->assertNotEmpty($matchingRules);
    }

    public function testGetForTypeTask()
    {
        $this->markTestSkipped('We are not longer matching task type in inbox rules. Must use saved search.');

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

        $this->assertEquals($task->process_id, $matchingTasks[0]['process_id']);
    }

    public function testGetForTypeSavedSearch()
    {
        $user = User::factory()->create();
        ProcessCategory::factory()->create(['is_system' => true, 'name' => 'System']);
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
        $matchingTasks = MatchingTasks::get($inboxRule);
        $this->assertCount(1, $matchingTasks);
        $this->assertEquals($activeTask->process_id, $matchingTasks[0]['process_id']);
    }
}
