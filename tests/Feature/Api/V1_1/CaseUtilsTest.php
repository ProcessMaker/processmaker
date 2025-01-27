<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Constants\CaseStatusConstants;
use ProcessMaker\Repositories\CaseUtils;

class CaseUtilsTest extends TestCase
{
    public function test_get_case_number_by_keywords(): void
    {
        $caseNumber = 12345;
        $expected = 'cn_1 cn_12 cn_123 cn_1234 cn_12345';
        $this->assertEquals($expected, CaseUtils::getCaseNumberByKeywords($caseNumber));
    }

    public function test_get_keywords(): void
    {
        $dataKeywords = ['case_number' => 12345, 'other' => 'keyword'];
        $expected = 'cn_1 cn_12 cn_123 cn_1234 cn_12345 keyword';
        $this->assertEquals($expected, CaseUtils::getKeywords($dataKeywords));
    }

    public function test_store_processes(): void
    {
        $processes = new Collection();
        $processData = ['id' => 1, 'name' => 'Process 1'];
        $result = CaseUtils::storeProcesses($processes, $processData);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($processData, $result->first());
    }

    public function test_store_requests(): void
    {
        $requests = new Collection();
        $requestData = ['id' => 1, 'name' => 'Request 1', 'parent_request_id' => null];
        $result = CaseUtils::storeRequests($requests, $requestData);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($requestData, $result->first());
    }

    public function test_store_request_tokens(): void
    {
        $requestTokens = new Collection();
        $tokenId = 1;
        $result = CaseUtils::storeRequestTokens($requestTokens, $tokenId);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($tokenId, $result->first());
    }

    public function test_store_tasks(): void
    {
        $tasks = new Collection();
        $taskData = [
            'id' => 1,
            'element_id' => 101,
            'name' => 'Task 1',
            'process_id' => 1001,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ];
        $result = CaseUtils::storeTasks($tasks, $taskData);
        $this->assertEquals(1, $result->count());
        $this->assertEquals([
            'id' => 1,
            'element_id' => 101,
            'name' => 'Task 1',
            'process_id' => 1001,
            'status' => 'ACTIVE',
        ], $result->first());
    }

    public function test_store_participants(): void
    {
        $participants = new Collection();
        $participantId = 1;
        $result = CaseUtils::storeParticipants($participants, $participantId);
        $this->assertEquals(1, $result->count());
        $this->assertEquals($participantId, $result->first());
    }

    public function test_store_processes_with_empty_data(): void
    {
        $processes = new Collection();
        $processData = [];
        $result = CaseUtils::storeProcesses($processes, $processData);
        $this->assertEquals(0, $result->count());
    }

    public function test_store_requests_with_empty_data(): void
    {
        $requests = new Collection();
        $requestData = [];
        $result = CaseUtils::storeRequests($requests, $requestData);
        $this->assertEquals(0, $result->count());
    }

    public function test_store_request_tokens_with_empty_data(): void
    {
        $requestTokens = new Collection();
        $tokenId = null;
        $result = CaseUtils::storeRequestTokens($requestTokens, $tokenId);
        $this->assertEquals(0, $result->count());
    }

    public function test_store_tasks_with_empty_data(): void
    {
        $tasks = new Collection();
        $taskData = [];
        $result = CaseUtils::storeTasks($tasks, $taskData);
        $this->assertEquals(0, $result->count());
    }

    public function test_store_participants_with_empty_data(): void
    {
        $participants = new Collection();
        $participantId = null;
        $result = CaseUtils::storeParticipants($participants, $participantId);
        $this->assertEquals(0, $result->count());
    }

    public function test_extract_data_process(): void
    {
        $object = (object)[
            'process' => (object)[
                'id' => 1,
                'name' => 'Process 1',
            ],
        ];
        $expected = [
            'id' => 1,
            'name' => 'Process 1',
        ];
        $this->assertEquals($expected, CaseUtils::extractData($object->process, 'PROCESS'));
    }

    public function test_extract_data_request(): void
    {
        $object = (object)[
            'processRequest' => (object)[
                'id' => 1,
                'name' => 'Request 1',
                'parentRequest' => (object)[
                    'id' => 2,
                ],
            ],
        ];
        $expected = [
            'id' => 1,
            'name' => 'Request 1',
            'parent_request_id' => 2,
        ];
        $this->assertEquals($expected, CaseUtils::extractData($object->processRequest, 'REQUEST'));
    }

    public function test_extract_data_task(): void
    {
        $object = (object)[
            'id' => 1,
            'element_id' => 101,
            'element_name' => 'Task 1',
            'process_id' => 1001,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ];
        $expected = [
            'id' => 1,
            'element_id' => 101,
            'name' => 'Task 1',
            'process_id' => 1001,
            'element_type' => 'task',
            'status' => 'ACTIVE',
        ];
        $this->assertEquals($expected, CaseUtils::extractData($object, 'TASK'));
    }

    public function test_get_status_in_progress(): void
    {
        $instanceStatus = CaseStatusConstants::ACTIVE;
        $expected = CaseStatusConstants::IN_PROGRESS;
        $this->assertEquals($expected, CaseUtils::getStatus($instanceStatus));
    }

    public function test_get_status_completed(): void
    {
        $instanceStatus = CaseStatusConstants::COMPLETED;
        $expected = CaseStatusConstants::COMPLETED;
        $this->assertEquals($expected, CaseUtils::getStatus($instanceStatus));
    }

    public function test_get_status_error(): void
    {
        $instanceStatus = 'ERROR';
        $expected = 'ERROR';
        $this->assertEquals($expected, CaseUtils::getStatus($instanceStatus));
    }
}
