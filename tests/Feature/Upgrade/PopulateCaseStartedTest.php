<?php

namespace Tests\Upgrades;

use Illuminate\Support\Facades\DB;
use PopulateCaseStarted;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class PopulateCaseStartedTest extends TestCase
{
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

    public function testTokensColumn()
    {
        // Instantiate PopulateCaseStarted
        $populateCaseStarted = new PopulateCaseStarted();

        // Confirm initial empty state of cases_started table
        $initialCount = DB::table('cases_started')->count();
        $this->assertEquals(0, $initialCount, 'cases_started should be empty before migration');

        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);
        // Create sample ProcessRequests with related data
        $processRequests = ProcessRequest::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);
        // Get the ID of the first process request to use as the shared process_request_id
        $processRequest = $processRequests->first();
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'element_type' => 'startEvent',
        ]);
        $processRequestTokens = ProcessRequestToken::factory()->count(3)->create([
            'process_request_id' => $processRequest->id,
            'element_type' => 'task',
        ]);
        $processRequestTokenIds = $processRequestTokens->pluck('id')->toArray();
        // Run the migration
        $populateCaseStarted->up();

        // Confirm record insertion into cases_started
        $finalCount = DB::table('cases_started')->count();
        $this->assertEquals($processRequests->count(), $finalCount, 'cases_started count should match the number of ProcessRequests created');
        $casesStartedRecord = DB::table('cases_started')->where('case_number', $processRequest->case_number)->first();
        $this->assertNotNull($casesStartedRecord, 'Each ProcessRequest should have a corresponding record in cases_started');

        $tokensArray = json_decode($casesStartedRecord->request_tokens, true);
        $this->assertEqualsCanonicalizing($tokensArray, $processRequestTokenIds);
    }

    public function testParticipatedColumn()
    {
        // Instantiate PopulateCaseStarted
        $populateCaseStarted = new PopulateCaseStarted();

        // Confirm initial empty state of cases_started table
        $initialCount = DB::table('cases_started')->count();
        $this->assertEquals(0, $initialCount, 'cases_started should be empty before migration');

        // Create first user
        $user1 = User::factory()->create();
        $this->actingAs($user1);
        // Create second user
        $user2 = User::factory()->create();
        $this->actingAs($user2);

        // Create sample ProcessRequests with related data
        $processRequests = ProcessRequest::factory()->create([
            'user_id' => $user1->id,
        ]);
        $processRequests = ProcessRequest::factory()->create([
            'user_id' => $user2->id,
        ]);
        // Get the ID of the first process request to use as the shared process_request_id
        $processRequest = $processRequests->first();

        // Create ProcessRequestTokens for processRequest
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'element_type' => 'startEvent',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->count(2)->create([
            'process_request_id' => $processRequest->id,
            'element_type' => 'task',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->count(2)->create([
            'process_request_id' => $processRequest->id,
            'element_type' => 'task',
            'user_id' => $user2->id,
        ]);
        $processRequestTokens = DB::table('process_request_tokens')->where('process_request_id', $processRequest->id);
        $participantIds = $processRequestTokens->pluck('user_id')->toArray();
        $uniqueParticipantIds = array_unique($participantIds);
        // Run the migration
        $populateCaseStarted->up();

        // Confirm record insertion into cases_started
        $finalCount = DB::table('cases_started')->count();
        $this->assertEquals($processRequests->count(), $finalCount, 'cases_started count should match the number of ProcessRequests created');
        $casesStartedRecord = DB::table('cases_started')->where('case_number', $processRequest->case_number)->first();
        $this->assertNotNull($casesStartedRecord, 'Each ProcessRequest should have a corresponding record in cases_started');
        // Prepare the participants record
        $tokensArray = json_decode($casesStartedRecord->participants, true);
        // Assert both arrays contain the same items in any order
        $this->assertEqualsCanonicalizing($tokensArray, $uniqueParticipantIds);
    }

    public function testCasesStartedProcessColumn()
    {
        // Instantiate the migration class
        $populateCaseStarted = new PopulateCaseStarted();

        // Confirm the initial state of the `cases_started` table
        $initialCount = DB::table('cases_started')->count();
        $this->assertEquals(0, $initialCount, 'cases_started table should be empty before migration');

        // Create test users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        // Create test processes
        $process1 = Process::factory()->create();
        $process2 = Process::factory()->create();
        // Create sample ProcessRequests for both users
        $processRequest1 = ProcessRequest::factory()->create([
            'user_id' => $user1->id,
            'name' => $process1->name,
            'process_id' => $process1->id,
        ]);
        $processRequest2 = ProcessRequest::factory()->create([
            'user_id' => $user2->id,
            'parent_request_id' => $processRequest1->id,
            'name' => $process2->name,
            'process_id' => $process2->id,
        ]);

        // Create ProcessRequestTokens for processRequest1
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'startEvent',
            'status' => 'TRIGGERED',
            'element_id' => 'node_1',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'callActivity',
            'status' => 'CLOSED',
            'element_id' => 'node_51',
            'user_id' => $user1->id,
            'subprocess_request_id' => $processRequest2->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'element_id' => 'node_12',
            'user_id' => $user1->id,
        ]);

        // Create ProcessRequestTokens for processRequest2
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'startEvent',
            'status' => 'TRIGGERED',
            'element_id' => 'node_1',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'element_id' => 'node_2',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'element_id' => 'node_12',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'end_event',
            'status' => 'CLOSED',
            'element_id' => 'node_25',
            'user_id' => null,
        ]);

        // Run the migration
        $populateCaseStarted->up();

        // Validate that the ProcessRequest1 has a corresponding entry in `cases_started`
        $casesStartedRecord = DB::table('cases_started')->where('case_number', $processRequest1->case_number)->first();
        $this->assertNotNull($casesStartedRecord, 'ProcessRequest should have a corresponding record in cases_started');

        // Expected data structure for `cases_started.processes` column
        $expectedData = [
            (object) ['id' => $processRequest1->process_id, 'name' => $processRequest1->name],
            (object) ['id' => $processRequest2->process_id, 'name' => $processRequest2->name],
        ];

        // Decode the JSON data from `cases_started.processes`
        $processesArray = json_decode($casesStartedRecord->processes);

        // Assert both arrays contain the same items in any order
        $this->assertEqualsCanonicalizing($expectedData, $processesArray, 'The processes data in cases_started should match the expected data');
    }

    public function testCasesStartedRequestsColumn()
    {
        // Instantiate the migration class
        $populateCaseStarted = new PopulateCaseStarted();

        // Confirm the initial state of the `cases_started` table
        $initialCount = DB::table('cases_started')->count();
        $this->assertEquals(0, $initialCount, 'cases_started table should be empty before migration');

        // Create test users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        // Create test processes
        $process1 = Process::factory()->create();
        $process2 = Process::factory()->create();
        // Create sample ProcessRequests for both users
        $processRequest1 = ProcessRequest::factory()->create([
            'user_id' => $user1->id,
            'name' => $process1->name,
            'process_id' => $process1->id,
        ]);
        $processRequest2 = ProcessRequest::factory()->create([
            'user_id' => $user2->id,
            'parent_request_id' => $processRequest1->id,
            'name' => $process2->name,
            'process_id' => $process2->id,
        ]);

        // Create ProcessRequestTokens for processRequest1
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'startEvent',
            'status' => 'TRIGGERED',
            'element_id' => 'node_1',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'callActivity',
            'status' => 'CLOSED',
            'element_id' => 'node_51',
            'user_id' => $user1->id,
            'subprocess_request_id' => $processRequest2->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest1->id,
            'process_id' => $processRequest1->process_id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'element_id' => 'node_12',
            'user_id' => $user1->id,
        ]);

        // Create ProcessRequestTokens for processRequest2
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'startEvent',
            'status' => 'TRIGGERED',
            'element_id' => 'node_1',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'element_id' => 'node_2',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'element_id' => 'node_12',
            'user_id' => $user1->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest2->id,
            'process_id' => $processRequest2->process_id,
            'element_type' => 'end_event',
            'status' => 'CLOSED',
            'element_id' => 'node_25',
            'user_id' => null,
        ]);

        // Run the migration
        $populateCaseStarted->up();

        // Validate that the ProcessRequest1 has a corresponding entry in `cases_started`
        $casesStartedRecord = DB::table('cases_started')->where('case_number', $processRequest1->case_number)->first();
        $this->assertNotNull($casesStartedRecord, 'ProcessRequest should have a corresponding record in cases_started');

        // Expected data structure for `cases_started.processes` column
        $expectedData = [
            (object) ['id' => $processRequest1->id, 'name' => $processRequest1->name, 'parent_request_id' => $processRequest1->parent_request_id],
            (object) ['id' => $processRequest2->id, 'name' => $processRequest2->name, 'parent_request_id' => $processRequest2->parent_request_id],
        ];

        // Decode the JSON data from `cases_started.requests`
        $requestsArray = json_decode($casesStartedRecord->requests);

        // Assert both arrays contain the same items in any order
        $this->assertEqualsCanonicalizing($expectedData, $requestsArray, 'The requests data in cases_started should match the expected data');
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
