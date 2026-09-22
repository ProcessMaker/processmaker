<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Group as TestGroup;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Regression for tasks index 1052 (ambiguous user_id) when a non-admin
 * lists tasks ordered by process_requests.case_title.
 */
#[TestGroup('process_tests')]
class TaskListNonAdminCaseTitleOrderTest extends TestCase
{
    use RequestHelper;

    public function testNonAdminCanListTasksOrderedByCaseTitleDesc()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $otherUser = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);

        $zetaRequest = ProcessRequest::factory()->create();
        $alphaRequest = ProcessRequest::factory()->create();
        $otherRequest = ProcessRequest::factory()->create();
        $zetaRequest->forceFill(['case_title' => 'Zeta mandate'])->saveQuietly();
        $alphaRequest->forceFill(['case_title' => 'Alpha mandate'])->saveQuietly();
        $otherRequest->forceFill(['case_title' => 'Omega mandate'])->saveQuietly();

        $zetaTask = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $zetaRequest->process_id,
            'process_request_id' => $zetaRequest->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);
        $alphaTask = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $alphaRequest->process_id,
            'process_request_id' => $alphaRequest->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);
        ProcessRequestToken::factory()->create([
            'user_id' => $otherUser->id,
            'process_id' => $otherRequest->process_id,
            'process_request_id' => $otherRequest->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);

        $response = $this->apiCall('GET', '/tasks', [
            'page' => 1,
            'include' => 'process,processRequest,processRequest.user,user',
            'pmql' => '(user_id = ' . $this->user->id . ')',
            'per_page' => 15,
            'order_by' => 'process_requests.case_title',
            'order_direction' => 'desc',
            'non_system' => true,
            'processesIManage' => 'false',
            'advanced_filter' => json_encode([
                [
                    'subject' => ['type' => 'Status'],
                    'operator' => '=',
                    'value' => 'In Progress',
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'is_priority'],
                    'operator' => '=',
                    'value' => true,
                ],
            ]),
        ]);

        $response->assertStatus(200);

        $rows = $response->json('data');
        $this->assertCount(2, $rows);
        $this->assertSame(
            [$zetaTask->id, $alphaTask->id],
            array_column($rows, 'id')
        );
        $this->assertSame(
            ['Zeta mandate', 'Alpha mandate'],
            array_map(fn ($row) => $row['process_request']['case_title'] ?? null, $rows)
        );
    }

    public function testNonAdminCanListTasksOrderedByCaseTitleDescAndFulltextSearch()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
        $otherUser = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);

        $zetaRequest = ProcessRequest::factory()->create();
        $alphaRequest = ProcessRequest::factory()->create();
        $otherRequest = ProcessRequest::factory()->create();
        $zetaRequest->forceFill(['case_title' => 'Zeta mandate'])->saveQuietly();
        $alphaRequest->forceFill(['case_title' => 'Alpha mandate'])->saveQuietly();
        $otherRequest->forceFill(['case_title' => 'Omega mandate'])->saveQuietly();

        $zetaTask = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $zetaRequest->process_id,
            'process_request_id' => $zetaRequest->id,
            'element_name' => 'Review of the client name',
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);
        $alphaTask = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $alphaRequest->process_id,
            'process_request_id' => $alphaRequest->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);
        ProcessRequestToken::factory()->create([
            'user_id' => $otherUser->id,
            'process_id' => $otherRequest->process_id,
            'process_request_id' => $otherRequest->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => false,
            'is_priority' => true,
            'completed_at' => null,
        ]);

        $response = $this->apiCall('GET', '/tasks', [
            'page' => 1,
            'include' => 'process,processRequest,processRequest.user,user',
            'pmql' => '(user_id = ' . $this->user->id . ') AND (fulltext LIKE "%Review of the client name%") ',
            'per_page' => 15,
            'order_by' => 'process_requests.case_title',
            'order_direction' => 'desc',
            'non_system' => true,
            'processesIManage' => 'false',
            'advanced_filter' => json_encode([
                [
                    'subject' => ['type' => 'Status'],
                    'operator' => '=',
                    'value' => 'In Progress',
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'is_priority'],
                    'operator' => '=',
                    'value' => true,
                ],
            ]),
        ]);

        $response->assertStatus(200);

        $rows = $response->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame(
            [$zetaTask->id],
            array_column($rows, 'id')
        );
        $this->assertSame(
            ['Zeta mandate'],
            array_map(fn ($row) => $row['process_request']['case_title'] ?? null, $rows)
        );
    }
}
