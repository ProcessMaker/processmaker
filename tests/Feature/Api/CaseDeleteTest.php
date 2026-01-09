<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseDeleteTest extends TestCase
{
    use RequestHelper;

    public function testDeleteCaseRemovesCoreRecords(): void
    {
        $caseNumber = 12345;
        $requests = ProcessRequest::factory()
            ->count(2)
            ->withCaseNumber($caseNumber)
            ->create();

        CaseNumber::query()->create(['process_request_id' => $requests->first()->id]);
        CaseNumber::query()->create(['process_request_id' => $requests->last()->id]);
        CaseStarted::factory()->create(['case_number' => $caseNumber]);
        CaseParticipated::factory()->create(['case_number' => $caseNumber]);

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('process_requests', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('cases_started', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('cases_participated', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('case_numbers', ['process_request_id' => $requests->first()->id]);
        $this->assertDatabaseMissing('case_numbers', ['process_request_id' => $requests->last()->id]);
    }

    public function testDeleteCaseReturnsNotFoundWhenMissing(): void
    {
        $caseNumber = 99999;

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(404);
    }
}
