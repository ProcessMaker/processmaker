<?php

namespace Tests\Feature\Cases;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\CaseSyncRepository;
use Tests\TestCase;

class CaseSyncRepositoryTest extends TestCase
{
    public function testSyncCases()
    {
        // Create some ProcessRequest instances
        $processRequest1 = ProcessRequest::factory()->create();
        $processRequest2 = ProcessRequest::factory()->create(['parent_request_id' => $processRequest1->id, 'case_number' => $processRequest1->case_number]);

        // Create some tokens for both ProcessRequest instances
        $processRequest1->tokens()->createMany([
            ProcessRequestToken::factory()->make([
                'element_name' => 'Task 1',
                'element_type' => 'task',
                'status' => 'ACTIVE',
                'element_id' => 'task-1',
                'process_request_id' => $processRequest1->id,
                'process_id' => $processRequest1->process_id,
            ])->toArray(),
            ProcessRequestToken::factory()->make([
                'element_name' => 'Task 2',
                'element_type' => 'task',
                'status' => 'CLOSED',
                'element_id' => 'task-2',
                'process_request_id' => $processRequest1->id,
                'process_id' => $processRequest1->process_id,
            ])->toArray(),
            ProcessRequestToken::factory()->make([
                'element_name' => 'Task 3',
                'element_type' => 'callActivity',
                'status' => 'ACTIVE',
                'element_id' => 'call-activity-1',
                'process_request_id' => $processRequest1->id,
                'process_id' => $processRequest1->process_id,
            ])->toArray(),
        ]);

        $processRequest2->tokens()->createMany([
            ProcessRequestToken::factory()->make([
                'element_name' => 'Task 4',
                'element_type' => 'scriptTask',
                'status' => 'CLOSED',
                'element_id' => 'task-4',
                'process_request_id' => $processRequest2->id,
                'process_id' => $processRequest2->process_id,
            ])->toArray(),
            ProcessRequestToken::factory()->make([
                'element_name' => 'Task 5',
                'element_type' => 'task',
                'status' => 'ACTIVE',
                'element_id' => 'task-5',
                'process_request_id' => $processRequest2->id,
                'process_id' => $processRequest2->process_id,
            ])->toArray(),
        ]);

        // Call the syncCases method
        $result = CaseSyncRepository::syncCases([$processRequest1->id, $processRequest2->id]);

        // Assert that the response contains the correct data
        $this->assertArrayHasKey('successes', $result);
        $this->assertArrayHasKey('errors', $result);

        // Check that the successes array contains the case numbers
        $this->assertContains($processRequest1->case_number, $result['successes']);
        $this->assertContains($processRequest2->case_number, $result['successes']);

        // Check that the CaseStarted records were created
        $caseStarted = DB::table('cases_started')->where('case_number', $processRequest1->case_number)->first();
        $tasks = json_decode($caseStarted->tasks, true);
        $participants = json_decode($caseStarted->participants, true);

        $task1 = $processRequest1->tokens()->where('element_id', 'task-1')->first();
        $task2 = $processRequest1->tokens()->where('element_id', 'task-2')->first();
        $task5 = $processRequest2->tokens()->where('element_id', 'task-5')->first();

        $expectedTasks = [
            [
                'id' => (string) $task5->id,
                'name' => 'Task 5',
                'status' => 'ACTIVE',
                'element_id' => 'task-5',
                'process_id' => $processRequest2->process_id,
                'user_id' => $task5->user_id,
            ],
            [
                'id' => (string) $task2->id,
                'name' => 'Task 2',
                'status' => 'CLOSED',
                'element_id' => 'task-2',
                'process_id' => $processRequest1->process_id,
                'user_id' => $task2->user_id,
            ],
            [
                'id' => (string) $task1->id,
                'name' => 'Task 1',
                'status' => 'ACTIVE',
                'element_id' => 'task-1',
                'process_id' => $processRequest1->process_id,
                'user_id' => $task1->user_id,
            ],
        ];
        $expectedParticipants = [
            $task1->user_id,
            $task2->user_id,
            $task5->user_id,
        ];

        $this->assertEquals($expectedTasks, $tasks);
        $this->assertEquals($expectedParticipants, $participants);

        // There are 3 case_participated records created
        $this->assertDatabaseCount('cases_participated', 3);

        // participant assigned to task-1 sees only their own tasks
        $caseParticipated = DB::table('cases_participated')
            ->where('case_number', $processRequest1->case_number)
            ->where('user_id', $task1->user_id)
            ->first();
        $participatedTasks = json_decode($caseParticipated->tasks, true);
        $participants = json_decode($caseParticipated->participants, true);
        $expectedUser1Tasks = array_values(array_filter(
            $expectedTasks,
            fn ($task) => $task['user_id'] === $task1->user_id
        ));
        $this->assertEquals($expectedUser1Tasks, $participatedTasks);
        $this->assertEquals($expectedParticipants, $participants);

        // participant assigned to task-2 sees only their own tasks
        $caseParticipated = DB::table('cases_participated')
            ->where('case_number', $processRequest1->case_number)
            ->where('user_id', $task2->user_id)
            ->first();
        $participatedTasks = json_decode($caseParticipated->tasks, true);
        $participants = json_decode($caseParticipated->participants, true);
        $expectedUser2Tasks = array_values(array_filter(
            $expectedTasks,
            fn ($task) => $task['user_id'] === $task2->user_id
        ));
        $this->assertEquals($expectedUser2Tasks, $participatedTasks);
        $this->assertEquals($expectedParticipants, $participants);

        // participant assigned to task-5 sees only their own tasks
        $caseParticipated = DB::table('cases_participated')
            ->where('case_number', $processRequest1->case_number)
            ->where('user_id', $task5->user_id)
            ->first();
        $participatedTasks = json_decode($caseParticipated->tasks, true);
        $expectedUser5Tasks = array_values(array_filter(
            $expectedTasks,
            fn ($task) => $task['user_id'] === $task5->user_id
        ));
        $this->assertEquals($expectedUser5Tasks, $participatedTasks);
    }
}
