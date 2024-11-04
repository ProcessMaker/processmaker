<?php

namespace Tests\Upgrades;

use Illuminate\Support\Facades\DB;
use Mockery;
use PopulateCaseStarted;
use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;

// use Illuminate\Foundation\Testing\RefreshDatabase;

class PopulateCaseStartedTest extends TestCase
{
    // use RefreshDatabase;

    protected function setUp(): void
    {
        require_once base_path('upgrades/2024_10_09_032151_populate_case_started.php');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Drop temporary tables and reset any specific configurations
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_requests_temp');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS process_request_tokens_tmp');
        DB::table('cases_started')->delete();
        parent::tearDown();
    }

    public function testUp()
    {
        // Instantiate PopulateCaseStarted
        $populateCaseStarted = new PopulateCaseStarted();

        // Confirm initial empty state of cases_started table
        $initialCount = DB::table('cases_started')->count();
        $this->assertEquals(0, $initialCount, 'cases_started should be empty before migration');

        // Create sample ProcessRequests with related data
        $processRequests = ProcessRequest::factory()->count(5)->create();

        // Run the migration
        $populateCaseStarted->up();

        // Confirm record insertion into cases_started
        $finalCount = DB::table('cases_started')->count();
        $this->assertEquals($processRequests->count(), $finalCount, 'cases_started count should match the number of ProcessRequests created');

        foreach ($processRequests as $processRequest) {
            $casesStartedRecord = DB::table('cases_started')->where('case_number', $processRequest->case_number)->first();
            $this->assertNotNull($casesStartedRecord, 'Each ProcessRequest should have a corresponding record in cases_started');

            // Validate field mapping and transformations
            $this->assertSame($processRequest->user_id, $casesStartedRecord->user_id);
            $this->assertSame($processRequest->case_number, $casesStartedRecord->case_number);
            $this->assertSame($processRequest->case_title, $casesStartedRecord->case_title);
            $this->assertSame($processRequest->case_title_formatted, $casesStartedRecord->case_title_formatted);
            $this->assertSame($this->mapStatus($processRequest->status), $casesStartedRecord->case_status);
            $this->assertEquals($processRequest->initiated_at, $casesStartedRecord->initiated_at);
            $this->assertEquals($processRequest->completed_at, $casesStartedRecord->completed_at);
            $this->assertEquals($processRequest->created_at, $casesStartedRecord->created_at);
            $this->assertEquals($processRequest->updated_at, $casesStartedRecord->updated_at);
        }
    }

    /**
     * Helper function to map ProcessRequest status to cases_started status
     *
     * @param string $status ProcessRequest status
     * @return string cases_started status
     */
    private function mapStatus($status)
    {
        return $status === 'ACTIVE' ? 'IN_PROGRESS' : $status;
    }
}
