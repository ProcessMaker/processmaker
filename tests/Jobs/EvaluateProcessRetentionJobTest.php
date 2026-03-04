<?php

namespace Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
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
        // Create a process with a 1 year retention period
        // retention_updated_at defaults to now, so cutoff is 12 months ago (now - 12 months)
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
            ],
        ]);

        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Delete the auto-created CaseNumber from ProcessRequestObserver
        // so we can test ProcessRequest deletion when all cases are removed
        CaseNumber::where('process_request_id', $processRequest->id)->delete();

        // Create a case number created 13 months ago
        // Cutoff = now - 12 months = 12 months ago
        // 13 months ago < 12 months ago, so it should be deleted
        $oldCaseCreatedAt = Carbon::now()->subMonths(13)->toIso8601String();
        $caseOld = CaseNumber::factory()->create([
            'created_at' => $oldCaseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);
        $casesStartedOld = CaseStarted::factory()->create([
            'case_number' => $caseOld->id,
        ]);
        $casesParticipatedOld = CaseParticipated::factory()->create([
            'case_number' => $caseOld->id,
        ]);
        $this->assertEquals($processRequest->id, $caseOld->process_request_id);
        $this->assertEquals($oldCaseCreatedAt, $caseOld->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Check that the case old has been deleted
        $this->assertNull(CaseNumber::find($caseOld->id), 'The case number should be deleted');
        $this->assertNull(CaseStarted::find($casesStartedOld->id), 'The cases_started should be deleted');
        $this->assertNull(CaseParticipated::find($casesParticipatedOld->id), 'The cases_participated should be deleted');
        // Check that the ProcessRequest has been deleted
        $this->assertNull(ProcessRequest::find($processRequest->id), 'The process request should be deleted');
    }

    public function testItDoesNotDeleteCasesThatAreWithinRetentionPeriod()
    {
        // Create a process with a 1 year retention period
        // retention_updated_at defaults to now, so cutoff is 12 months ago (now - 12 months)
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
            ],
        ]);
        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Create a case number created 5 months ago
        // Cutoff = now - 12 months = 12 months ago
        // 5 months ago is NOT < 12 months ago, so it should NOT be deleted
        $caseCreatedAt = Carbon::now()->subMonths(5)->toIso8601String();
        $case = CaseNumber::factory()->create([
            'created_at' => $caseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);
        // Create a cases_started for the case
        $casesStarted = CaseStarted::factory()->create([
            'case_number' => $case->id,
        ]);
        // Create a cases_participated for the case
        $casesParticipated = CaseParticipated::factory()->create([
            'case_number' => $case->id,
        ]);
        // Assert the case, cases_started, and cases_participated are created
        $this->assertEquals($processRequest->id, $case->process_request_id);
        $this->assertEquals($caseCreatedAt, $case->created_at->toIso8601String());

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Check that the case has not been deleted
        $this->assertNotNull(CaseNumber::find($case->id), 'The case should not be deleted');
        $this->assertNotNull(CaseStarted::find($casesStarted->id), 'The cases_started should not be deleted');
        $this->assertNotNull(CaseParticipated::find($casesParticipated->id), 'The cases_participated should not be deleted');
        // Check that the ProcessRequest has not been deleted (cases still exist)
        $this->assertNotNull(ProcessRequest::find($processRequest->id), 'The process request should not be deleted when cases still exist');
    }

    public function testItHandlesMultipleCasesInBatches()
    {
        // Create a process with a 1 year retention period
        // retention_updated_at defaults to now, so cutoff is 12 months ago (now - 12 months)
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
            ],
        ]);
        $process->save();
        $process->refresh();
        $this->assertEquals(self::RETENTION_PERIOD, $process->properties['retention_period']);

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();
        $this->assertEquals($process->id, $processRequest->process_id);

        // Delete the auto-created CaseNumber from ProcessRequestObserver
        // so we can test ProcessRequest deletion when all cases are removed
        CaseNumber::where('process_request_id', $processRequest->id)->delete();

        // Create 1200 cases (to test chunking/batch deletion)
        // These cases are created 13 months ago
        // Cutoff = now - 12 months = 12 months ago
        // 13 months ago < 12 months ago, so these should be deleted
        $cases = CaseNumber::factory()->count(1200)->sequence(function () use ($processRequest) {
            return ['process_request_id' => $processRequest->id, 'created_at' => Carbon::now()->subMonths(13)->toIso8601String()];
        })->create();
        $cases->each(function ($case) {
            // Create one CaseStarted per CaseNumber
            CaseStarted::factory()->create([
                'case_number' => $case->id,
            ]);
            // Create one CaseParticipated per CaseNumber
            CaseParticipated::factory()->create([
                'case_number' => $case->id,
            ]);
        });
        $this->assertEquals($processRequest->id, $cases->first()->process_request_id);

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Assert all old cases are deleted
        $this->assertDatabaseCount('case_numbers', 0);
        // Assert all case_started are deleted
        $this->assertDatabaseCount('cases_started', 0);
        // Assert all case_participated are deleted
        $this->assertDatabaseCount('cases_participated', 0);
        // Assert the ProcessRequest has been deleted (all its cases were deleted)
        $this->assertNull(ProcessRequest::find($processRequest->id), 'The process request should be deleted');
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
        // ProcessRequest should not be deleted (cases still exist)
        $this->assertNotNull(ProcessRequest::find($processRequest->id), 'The process request should not be deleted when cases still exist');
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
        $casesStartedOld = CaseStarted::factory()->create([
            'case_number' => $oldCase->id,
        ]);
        $casesParticipatedOld = CaseParticipated::factory()->create([
            'case_number' => $oldCase->id,
        ]);

        // Create a case 7 months ago (before retention_updated_at) that should NOT be deleted
        // Old cases cutoff = 6 months ago - 1 year = 18 months ago
        // 7 months ago is NOT < 18 months ago (7 months ago is more recent), so it should NOT be deleted
        $oldCaseNotDeletedDate = Carbon::now()->subMonths(7);
        $oldCaseNotDeleted = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $oldCaseNotDeleted->created_at = $oldCaseNotDeletedDate;
        $oldCaseNotDeleted->save();
        $casesStartedNotDeleted = CaseStarted::factory()->create([
            'case_number' => $oldCaseNotDeleted->id,
        ]);
        $casesParticipatedNotDeleted = CaseParticipated::factory()->create([
            'case_number' => $oldCaseNotDeleted->id,
        ]);

        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The 20-month-old case should be deleted (older than 18 months cutoff)
        // The 7-month-old case should NOT be deleted (newer than 18 months cutoff)
        // Plus the auto-created case = 2 total
        $this->assertNull(CaseNumber::find($oldCase->id), 'The 20-month-old case should be deleted');
        $this->assertNull(CaseStarted::find($casesStartedOld->id), 'The cases_started should be deleted');
        $this->assertNull(CaseParticipated::find($casesParticipatedOld->id), 'The cases_participated should be deleted');

        $this->assertNotNull(CaseNumber::find($oldCaseNotDeleted->id), 'The 7-month-old case should NOT be deleted');
        $this->assertNotNull(CaseStarted::find($casesStartedNotDeleted->id), 'The cases_started should NOT be deleted');
        $this->assertNotNull(CaseParticipated::find($casesParticipatedNotDeleted->id), 'The cases_participated should NOT be deleted');

        $this->assertDatabaseCount('case_numbers', 2);
        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseCount('cases_participated', 1);
        // ProcessRequest should not be deleted (some cases still exist)
        $this->assertNotNull(ProcessRequest::find($processRequest->id), 'The process request should not be deleted when some cases still exist');
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
        $casesStartedOld = CaseStarted::factory()->create([
            'case_number' => $oldCase->id,
        ]);
        $casesParticipatedOld = CaseParticipated::factory()->create([
            'case_number' => $oldCase->id,
        ]);
        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The case should NOT be deleted because retention policy is disabled
        // Plus the auto-created case = 2 total
        $this->assertNotNull(CaseNumber::find($oldCase->id), 'The case should NOT be deleted when retention policy is disabled');
        $this->assertNotNull(CaseStarted::find($casesStartedOld->id), 'The cases_started should NOT be deleted');
        $this->assertNotNull(CaseParticipated::find($casesParticipatedOld->id), 'The cases_participated should NOT be deleted');
        $this->assertDatabaseCount('case_numbers', 2);
        $this->assertDatabaseCount('cases_started', 1);
        // ProcessRequest should not be deleted (retention policy is disabled)
        $this->assertNotNull(ProcessRequest::find($processRequest->id), 'The process request should not be deleted when retention policy is disabled');

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
        $casesStartedOld = CaseStarted::factory()->create([
            'case_number' => $oldCase->id,
        ]);
        $casesParticipatedOld = CaseParticipated::factory()->create([
            'case_number' => $oldCase->id,
        ]);
        // Create a case created 5 months ago (within default 1 year retention)
        // 5 months ago is NOT < (now - 1 year), so it should NOT be deleted
        $newCaseDate = Carbon::now()->subMonths(5);
        $newCase = CaseNumber::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);
        $newCase->created_at = $newCaseDate;
        $newCase->save();
        $casesStartedNew = CaseStarted::factory()->create([
            'case_number' => $newCase->id,
        ]);
        $casesParticipatedNew = CaseParticipated::factory()->create([
            'case_number' => $newCase->id,
        ]);
        // Dispatch the job
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // The 13-month-old case should be deleted (older than 1 year default)
        // The 5-month-old case should NOT be deleted (within 1 year default)
        // Plus the auto-created case = 2 total
        $this->assertNull(CaseNumber::find($oldCase->id), 'The 13-month-old case should be deleted with default 1 year retention');
        $this->assertNull(CaseStarted::find($casesStartedOld->id), 'The cases_started should be deleted');
        $this->assertNull(CaseParticipated::find($casesParticipatedOld->id), 'The cases_participated should be deleted');
        $this->assertNotNull(CaseNumber::find($newCase->id), 'The 5-month-old case should NOT be deleted with default 1 year retention');
        $this->assertNotNull(CaseStarted::find($casesStartedNew->id), 'The cases_started should NOT be deleted');
        $this->assertNotNull(CaseParticipated::find($casesParticipatedNew->id), 'The cases_participated should NOT be deleted');
        $this->assertDatabaseCount('case_numbers', 2);
        $this->assertDatabaseCount('cases_started', 1);
        $this->assertDatabaseCount('cases_participated', 1);
        // ProcessRequest should not be deleted (some cases still exist)
        $this->assertNotNull(ProcessRequest::find($processRequest->id), 'The process request should not be deleted when some cases still exist');
    }

    public function testItDeletesAllRelatedRecordsWhenCasesExceedRetentionPeriod()
    {
        // Create a process with a 1 year retention period
        $process = Process::factory()->create([
            'properties' => [
                'retention_period' => self::RETENTION_PERIOD,
            ],
        ]);
        $process->save();
        $process->refresh();

        // Create a process request
        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        // Delete the auto-created CaseNumber from ProcessRequestObserver
        // so we can test ProcessRequest deletion when all cases are removed
        CaseNumber::where('process_request_id', $processRequest->id)->delete();

        // Create a process request token
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'process_id' => $process->id,
        ]);

        // Create a case number created 13 months ago (should be deleted)
        $oldCaseCreatedAt = Carbon::now()->subMonths(13)->toIso8601String();
        $caseOld = CaseNumber::factory()->create([
            'created_at' => $oldCaseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);

        // Create related records
        $casesStartedOld = CaseStarted::factory()->create([
            'case_number' => $caseOld->id,
        ]);
        $casesParticipatedOld = CaseParticipated::factory()->create([
            'case_number' => $caseOld->id,
        ]);

        // Create comments
        $commentOnRequest = Comment::factory()->create([
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $processRequest->id,
            'case_number' => $caseOld->id,
        ]);
        $commentOnToken = Comment::factory()->create([
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $token->id,
            'case_number' => $caseOld->id,
        ]);

        // Create media
        $requestMedia = Media::factory()->create([
            'model_type' => ProcessRequest::class,
            'model_id' => $processRequest->id,
            'custom_properties' => [
                'data_name' => 'case/file.txt',
            ],
        ]);

        // Create notification
        $notification = Notification::factory()->create([
            'data' => json_encode([
                'request_id' => $processRequest->id,
                'type' => 'TASK_CREATED',
            ]),
        ]);

        // Dispatch the job to evaluate the retention period
        EvaluateProcessRetentionJob::dispatchSync($process->id);

        // Check that all case-related records have been deleted
        $this->assertNull(CaseNumber::find($caseOld->id), 'The case number should be deleted');
        $this->assertNull(CaseStarted::find($casesStartedOld->id), 'The cases_started should be deleted');
        $this->assertNull(CaseParticipated::find($casesParticipatedOld->id), 'The cases_participated should be deleted');

        // Check that ProcessRequest has been deleted
        $this->assertNull(ProcessRequest::find($processRequest->id), 'The process request should be deleted');

        // Check that comments have been deleted
        $this->assertNull(Comment::find($commentOnRequest->id), 'The comment on ProcessRequest should be deleted');
        $this->assertNull(Comment::find($commentOnToken->id), 'The comment on ProcessRequestToken should be deleted');

        // Check that media has been deleted
        $this->assertNull(Media::find($requestMedia->id), 'The request media should be deleted');

        // Check that notification has been deleted
        $this->assertNull(Notification::find($notification->id), 'The notification should be deleted');
    }
}
