<?php

namespace Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;

class EvaluateProcessRetentionJobTest extends TestCase
{
    use RefreshDatabase;

    const RETENTION_PERIOD = '6_months';

    public function testItDeletesCasesThatExceedRetentionPeriod()
    {
        // Create a process with a 6 month retention period
        $retentionUpdatedAt = Carbon::now()->subMonths(6)->toIso8601String();
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
                'retention_updated_at' => $retentionUpdatedAt,
            ],
        ]);

        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);
        $this->assertEquals($retentionUpdatedAt, $process->properties['retention_updated_at']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Create a case number with a creation date that is past the retention period
        $oldCaseCreatedAt = Carbon::now()->subMonths(7)->toIso8601String();
        $caseOld = CaseNumber::factory()->create([
            'created_at' => $oldCaseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);
        $this->assertEquals($processRequest->id, $caseOld->process_request_id);
        $this->assertEquals($oldCaseCreatedAt, $caseOld->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Check that the case old has been deleted
        $this->assertNull(CaseNumber::find($caseOld->id));
    }

    public function testItDoesNotDeleteCasesThatAreWithinRetentionPeriod()
    {
        // Create a process with a 6 month retention period
        $retentionUpdatedAt = Carbon::now()->subMonths(6)->toIso8601String();
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
                'retention_updated_at' => $retentionUpdatedAt,
            ],
        ]);
        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);
        $this->assertEquals($retentionUpdatedAt, $process->properties['retention_updated_at']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Create a case number with a creation date that is within the retention period
        $caseCreatedAt = Carbon::now()->subMonths(5)->toIso8601String();
        $case = CaseNumber::factory()->create([
            'created_at' => $caseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);
        $this->assertEquals($processRequest->id, $case->process_request_id);
        $this->assertEquals($caseCreatedAt, $case->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Check that the case has not been deleted
        $this->assertNotNull(CaseNumber::find($case->id));
    }

    public function testItHandlesMultipleCasesInBatches()
    {
        // Create a process with a 6 month retention period
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
                'retention_updated_at' => Carbon::now()->subMonths(6)->toIso8601String(),
            ],
        ]);
        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);
        $this->assertEquals(Carbon::now()->subMonths(6)->toIso8601String(), $process->properties['retention_updated_at']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Create 1200 cases (to test chunking/batch deletion)
        // These cases should be deleted because they're older than the retention period
        // retention_updated_at is 6 months ago, so cases created 7+ months ago should be deleted
        $cases = CaseNumber::factory()->count(1200)->create([
            'process_request_id' => $processRequest->id,
            'created_at' => Carbon::now()->subMonths(7)->toIso8601String(),
        ]);
        $this->assertEquals($processRequest->id, $cases->first()->process_request_id);
        $this->assertEquals(Carbon::now()->subMonths(7)->toIso8601String(), $cases->first()->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Assert all old cases are deleted
        // There should be 1 case left (due to the creation of the process request) because the new case is within the retention period
        $this->assertDatabaseCount('case_numbers', 1);

        // TODO: Assert log entry is created
    }
}
