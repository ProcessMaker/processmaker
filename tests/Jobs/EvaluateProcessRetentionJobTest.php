<?php

namespace Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;

class EvaluateProcessRetentionJobTest extends TestCase
{
    use RefreshDatabase;

    const RETENTION_PERIOD = '1_year';

    protected function setUp(): void
    {
        parent::setUp();
        // Enable case retention policy for all tests
        Config::set('app.case_retention_policy_enabled', true);
    }

    protected function tearDown(): void
    {
        // Clean up config
        Config::set('app.case_retention_policy_enabled', false);
        parent::tearDown();
    }

    public function testItDeletesCasesThatExceedRetentionPeriod()
    {
        // Create a process with a 6 month retention period
        // retention_updated_at is 6 months ago, so old cases cutoff is 12 months ago (6 months ago - 6 months)
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

        // Create a case number created 13 months ago (before retention_updated_at)
        // Old cases cutoff = 6 months ago - 6 months = 12 months ago
        // 13 months ago < 12 months ago, so it should be deleted
        $oldCaseCreatedAt = Carbon::now()->subMonths(13)->toIso8601String();
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
        // retention_updated_at is 6 months ago, so old cases cutoff is 12 months ago (6 months ago - 6 months)
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

        // Create a case number created 5 months ago (before retention_updated_at)
        // This case is NOT older than the old cases cutoff (12 months ago), so it should NOT be deleted
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
        // These cases are created 13 months ago (before retention_updated_at)
        // Old cases cutoff = 6 months ago - 6 months = 12 months ago
        // 13 months ago < 12 months ago, so these should be deleted
        $cases = CaseNumber::factory()->count(1200)->create([
            'process_request_id' => $processRequest->id,
            'created_at' => Carbon::now()->subMonths(13)->toIso8601String(),
        ]);
        $this->assertEquals($processRequest->id, $cases->first()->process_request_id);
        $this->assertEquals(Carbon::now()->subMonths(13)->toIso8601String(), $cases->first()->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Assert all old cases are deleted
        // There should be 1 case left (the auto-created case from ProcessRequestObserver)
        // because it was created after retention_updated_at and is within the retention period
        $this->assertDatabaseCount('case_numbers', 1);
    }

    public function testItHandlesRetentionPolicyUpdate()
    {
        // Create a process with retention updated 6 months ago (was 6 months, now 1 year)
        $retentionUpdatedAt = Carbon::now()->subMonths(6)->toIso8601String();
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => '1_year', // Updated to 1 year
                'retention_updated_at' => $retentionUpdatedAt,
            ],
        ]);
        $process->save();
        $process->refresh();

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        // Create an old case (7 months ago, before retention_updated_at)
        // Old cases cutoff = 6 months ago - 1 year = 18 months ago
        // 7 months ago is NOT < 18 months ago, so it should NOT be deleted
        $oldCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
            'created_at' => Carbon::now()->subMonths(7)->toIso8601String(),
        ]);

        // Create a new case (1 month ago, after retention_updated_at)
        // New cases cutoff = now - 1 year = 12 months ago
        // 1 month ago is NOT < 12 months ago, so it should NOT be deleted
        $newCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
            'created_at' => Carbon::now()->subMonths(1)->toIso8601String(),
        ]);

        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Both cases should still exist (plus the auto-created one = 3 total)
        $this->assertNotNull(CaseNumber::find($oldCase->id));
        $this->assertNotNull(CaseNumber::find($newCase->id));
        $this->assertDatabaseCount('case_numbers', 3);
    }

    public function testItDeletesOldCasesAfterRetentionPolicyUpdate()
    {
        // Create a process with retention updated 6 months ago (was 6 months, now 1 year)
        $retentionUpdatedAt = Carbon::now()->subMonths(6)->toIso8601String();
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => '1_year', // Updated to 1 year
                'retention_updated_at' => $retentionUpdatedAt,
            ],
        ]);
        $process->save();
        $process->refresh();

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        // Create an old case (20 months ago, before retention_updated_at which is 6 months ago)
        // Old cases cutoff = 6 months ago - 1 year = 18 months ago
        // 20 months ago < 18 months ago (earlier date), so it SHOULD be deleted
        $oldCaseDate = Carbon::now()->subMonths(20);
        $oldCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $oldCase->created_at = $oldCaseDate;
        $oldCase->save();

        // Create a case 7 months ago (before retention_updated_at) that should NOT be deleted
        // Old cases cutoff = 6 months ago - 1 year = 18 months ago
        // 7 months ago is NOT < 18 months ago (7 months ago is more recent), so it should NOT be deleted
        $oldCaseNotDeletedDate = Carbon::now()->subMonths(7);
        $oldCaseNotDeleted = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $oldCaseNotDeleted->created_at = $oldCaseNotDeletedDate;
        $oldCaseNotDeleted->save();

        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The 20-month-old case should be deleted (older than 18 months cutoff)
        // The 7-month-old case should NOT be deleted (newer than 18 months cutoff)
        // Plus the auto-created case = 2 total
        $this->assertNull(CaseNumber::find($oldCase->id), 'The 20-month-old case should be deleted');
        $this->assertNotNull(CaseNumber::find($oldCaseNotDeleted->id), 'The 7-month-old case should NOT be deleted');
        $this->assertDatabaseCount('case_numbers', 2);
    }

    public function testItDoesNotRunWhenRetentionPolicyIsDisabled()
    {
        // Disable case retention policy
        Config::set('app.case_retention_policy_enabled', false);

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

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        // Create an old case that should be deleted if retention was enabled
        $oldCaseDate = Carbon::now()->subMonths(13);
        $oldCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $oldCase->created_at = $oldCaseDate;
        $oldCase->save();

        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The case should NOT be deleted because retention policy is disabled
        // Plus the auto-created case = 2 total
        $this->assertNotNull(CaseNumber::find($oldCase->id), 'The case should NOT be deleted when retention policy is disabled');
        $this->assertDatabaseCount('case_numbers', 2);

        // Re-enable for other tests
        Config::set('app.case_retention_policy_enabled', true);
    }

    public function testItDefaultsToOneYearForProcessesWithoutRetentionPeriod()
    {
        // Create a process WITHOUT retention_period property (should default to 1 year)
        $process = Process::factory()->create([
            'properties' => [], // No retention_period set
        ]);
        $process->save();
        $process->refresh();

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        // Create a case created 13 months ago (older than default 1 year retention)
        // Since retention_updated_at defaults to now, old cases cutoff = now - 1 year
        // 13 months ago < (now - 1 year), so it should be deleted
        $oldCaseDate = Carbon::now()->subMonths(13);
        $oldCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $oldCase->created_at = $oldCaseDate;
        $oldCase->save();

        // Create a case created 5 months ago (within default 1 year retention)
        // 5 months ago is NOT < (now - 1 year), so it should NOT be deleted
        $newCaseDate = Carbon::now()->subMonths(5);
        $newCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $newCase->created_at = $newCaseDate;
        $newCase->save();

        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The 13-month-old case should be deleted (older than 1 year default)
        // The 5-month-old case should NOT be deleted (within 1 year default)
        // Plus the auto-created case = 2 total
        $this->assertNull(CaseNumber::find($oldCase->id), 'The 13-month-old case should be deleted with default 1 year retention');
        $this->assertNotNull(CaseNumber::find($newCase->id), 'The 5-month-old case should NOT be deleted with default 1 year retention');
        $this->assertDatabaseCount('case_numbers', 2);
    }
}
