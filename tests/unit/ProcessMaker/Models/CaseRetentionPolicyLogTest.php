<?php

namespace Tests\Unit\ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Jobs\EvaluateProcessRetentionJob;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseRetentionPolicyLog;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;

class CaseRetentionPolicyLogTest extends TestCase
{
    use RefreshDatabase;

    public function testJobAddsLogRecordWhenCasesAreDeleted(): void
    {
        Config::set('app.case_retention_policy_enabled', true);

        $process = Process::factory()->create([
            'properties' => ['retention_period' => 'one_year'],
        ]);
        $process->save();
        $process->refresh();

        $processRequest = ProcessRequest::factory()->create();
        $processRequest->process_id = $process->id;
        $processRequest->save();
        $processRequest->refresh();

        CaseNumber::where('process_request_id', $processRequest->id)->delete();

        $oldCaseCreatedAt = Carbon::now()->subMonths(13)->toIso8601String();
        $caseOld = CaseNumber::factory()->create([
            'created_at' => $oldCaseCreatedAt,
            'process_request_id' => $processRequest->id,
        ]);
        CaseStarted::factory()->create(['case_number' => $caseOld->id]);
        CaseParticipated::factory()->create(['case_number' => $caseOld->id]);

        $this->assertDatabaseCount('case_retention_policy_logs', 0);

        EvaluateProcessRetentionJob::dispatchSync($process->id);

        $this->assertDatabaseCount('case_retention_policy_logs', 1);

        $log = CaseRetentionPolicyLog::first();
        $this->assertSame((int) $process->id, (int) $log->process_id);
        $this->assertSame(1, $log->deleted_count);
        $this->assertNotEmpty($log->total_time_taken);
        $this->assertIsNumeric($log->total_time_taken);
        $this->assertNotNull($log->deleted_at);

        $loggedCaseIds = json_decode($log->case_ids, true);
        $this->assertIsArray($loggedCaseIds);
        $this->assertContains((int) $caseOld->id, array_map('intval', $loggedCaseIds));

        Config::set('app.case_retention_policy_enabled', false);
    }
}
