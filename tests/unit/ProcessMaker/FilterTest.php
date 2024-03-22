<?php

namespace Tests;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class FilterTest extends TestCase
{
    use RequestHelper;

    private function filter($filterDefinition, $model = ProcessRequest::class)
    {
        $query = $model::query();
        Filter::filter($query, json_encode($filterDefinition));

        return $query->toRawSql();
    }

    public function testFormData()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'data.form_input_1'],
                'operator' => '=',
                'value' => 'abc',
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where (json_unquote(json_extract(`data`, '\$.\"form_input_1\"')) = 'abc')",
            $sql
        );
    }

    public function testCompareDataInteger()
    {
        $filter = [
            [
                'subject' => ['type' => 'Field', 'value' => 'data.form_input_1'],
                'operator' => '<',
                'value' => '6',
            ],
        ];

        $processRequest1 = ProcessRequest::factory()->create([
            'data' => ['form_input_1' => 5],
        ]);

        $task1 = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
        ]);

        $processRequest2 = ProcessRequest::factory()->create([
            'data' => ['form_input_1' => 7],
        ]);

        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
        ]);

        $response = $this->apiCall('GET', '/tasks', [
            'advanced_filter' => json_encode($filter),
        ]);

        $results = $response->json()['data'];

        $this->assertCount(1, $results);
        $this->assertEquals($task1->id, $results[0]['id']);
    }

    public function testNestedOr()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'name'],
                'operator' => '=',
                'value' => 'val1',
                'or' => [
                    [
                        'subject' => ['type' => 'Field', 'value' => 'name'],
                        'operator' => '=',
                        'value' => 'val2',
                        'or' => [
                            [
                                'subject' => ['type' => 'Field', 'value' => 'name'],
                                'operator' => '=',
                                'value' => 'val3',
                            ],
                        ],
                    ], [
                        'subject' => ['type' => 'Field', 'value' => 'name'],
                        'operator' => '=',
                        'value' => 'val4',
                    ],
                ],
            ],
            ['subject' => ['type' => 'Field', 'value' => 'name'],
                'operator' => '=',
                'value' => 'val5',
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`name` = 'val1' or ((`name` = 'val2' or (`name` = 'val3')) and `name` = 'val4')) and `name` = 'val5')",
            $sql
        );
    }

    public function testAdditionalOperators()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'foo'],
                'operator' => 'contains',
                'value' => 'abc',
                'or' => [
                    [
                        'subject' => ['type' => 'Field', 'value' => 'bar'],
                        'operator' => 'starts_with',
                        'value' => 'def',
                    ],
                ],
            ],
            [
                'subject' => ['type' => 'Field', 'value' => 'bat'],
                'operator' => 'between',
                'value' => [1, 10],
            ],
            [
                'subject' => ['type' => 'Field', 'value' => 'baz'],
                'operator' => 'in',
                'value' => [1, 2, 3],
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`foo` like '%abc%' or (`bar` like 'def%')) and `bat` between 1 and 10 and `baz` in (1, 2, 3))",
            $sql
        );
    }

    public function testParticipants()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $sql = $this->filter([
            [
                'subject' => ['type' => 'Participants'],
                'operator' => 'in',
                'value' => [$user1->id, $user2->id],
                'or' => [
                    [
                        'subject' => ['type' => 'Participants'],
                        'operator' => '=',
                        'value' => $user3->id,
                    ],
                ],
            ],
        ]);

        $this->assertEquals(
            'select * from `process_requests` where (((' .
                "`id` in (select `process_request_id` from `process_request_tokens` where `user_id` = {$user1->id} and `element_type` in ('task', 'userTask', 'startEvent'))) " .
                "or (`id` in (select `process_request_id` from `process_request_tokens` where `user_id` = {$user2->id} and `element_type` in ('task', 'userTask', 'startEvent'))) " .
                "or ((`id` in (select `process_request_id` from `process_request_tokens` where `user_id` = {$user3->id} and `element_type` in ('task', 'userTask', 'startEvent'))))))",
            $sql
        );
    }

    public function testRequestStatus()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Status'],
                'operator' => 'in',
                'value' => ['In Progress', 'Completed'],
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`status` = 'ACTIVE') or (`status` = 'COMPLETED'))",
            $sql
        );
    }

    public function testTaskStatus()
    {
        $user = User::factory()->create();

        $selfServiceTask = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null,
            'self_service_groups' => ['users' => [$user->id]],
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $sql = $this->filter([
            [
                'subject' => ['type' => 'Status'],
                'operator' => '=',
                'value' => 'Self Service',
            ],
        ], ProcessRequestToken::class);

        $this->assertEquals(
            "select * from `process_request_tokens` where ((`id` in ({$selfServiceTask->id})))",
            $sql
        );
    }

    public function testRequestProcess()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Process'],
                'operator' => 'in',
                'value' => [5, 6],
            ],
        ]);

        $this->assertEquals(
            'select * from `process_requests` where (`process_id` in (5, 6))',
            $sql
        );
    }

    public function testTaskProcess()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Process'],
                'operator' => '=',
                'value' => 5,
            ],
        ], ProcessRequestToken::class);

        $this->assertEquals(
            'select * from `process_request_tokens` where (`process_request_id` in (select `id` from `process_requests` where `process_id` in (5)))',
            $sql
        );
    }

    public function testTaskCaseTitle()
    {
        $request = ProcessRequest::factory()->create();
        $caseTitle = $request->case_title;

        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);

        $filter = [
            [
                'subject' => ['type' => 'Relationship', 'value' => 'processRequest.case_title'],
                'operator' => '=',
                'value' => $caseTitle,
            ],
        ];

        $query = ProcessRequestToken::query();
        Filter::filter($query, json_encode($filter));
        $this->assertEquals($query->first()->id, $task->id);
    }

    public function testTaskCaseNumber()
    {
        $request = ProcessRequest::factory()->create();
        $caseNumber = $request->case_number;

        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);

        $filter = [
            [
                'subject' => ['type' => 'Relationship', 'value' => 'processRequest.case_number'],
                'operator' => '=',
                'value' => $caseNumber,
            ],
        ];

        $query = ProcessRequestToken::query();
        Filter::filter($query, json_encode($filter));
        $this->assertEquals($query->first()->id, $task->id);
    }
}
