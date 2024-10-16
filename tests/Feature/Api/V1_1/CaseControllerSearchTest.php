<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseUtils;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseControllerSearchTest extends TestCase
{
    use RequestHelper, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = CaseControllerTest::createUser('user_a');
    }

    public function tearDown(): void
    {
        User::where('id', $this->user->id)->forceDelete();

        parent::tearDown();
    }

    public function test_search_all_cases_by_case_number(): void
    {
        CaseControllerTest::createCasesStartedForUser($this->user->id, 5);
        $caseNumber = 123456;
        CaseControllerTest::createCasesStartedForUser($this->user->id, 1, ['case_number' => $caseNumber, 'keywords' => CaseUtils::getCaseNumberByKeywords($caseNumber)]);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => $caseNumber]));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_in_progress_cases_by_case_number(): void
    {
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_status' => 'IN_PROGRESS']);
        $caseNumber = 123456;
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 1, [
            'case_number' => $caseNumber, 'case_status' => 'IN_PROGRESS', 'keywords' => CaseUtils::getCaseNumberByKeywords($caseNumber),
        ]);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress'));
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => $caseNumber]));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_completed_cases_by_case_number(): void
    {
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_status' => 'COMPLETED']);
        $caseNumber = 987654;
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 1, [
            'case_number' => $caseNumber, 'case_status' => 'COMPLETED', 'keywords' => CaseUtils::getCaseNumberByKeywords($caseNumber),
        ]);

        $response = $this->apiCall('GET', route('api.1.1.cases.completed'));
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => $caseNumber]));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_all_cases_by_case_title(): void
    {
        $caseTitle1 = 'Accident Insurance Registration Process';
        $caseTitle2 = 'ABE Spring';
        $caseTitle3 = 'Credit Evaluation Process';

        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'keywords' => $caseTitle1]);
        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle2, 'keywords' => $caseTitle2]);
        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle3, 'keywords' => $caseTitle3]);

        $this->assertDatabaseCount('cases_started', 15);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'insurance']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'spri']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'accident registration']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'accident evaluation']));
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => '(credit)']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_in_progress_cases_by_case_title(): void
    {
        $caseTitle1 = 'Accident Insurance Registration Process';
        $caseTitle2 = 'ABE Spring';
        $caseTitle3 = 'Credit Evaluation Process';

        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 10, ['case_title' => $caseTitle1, 'case_status' => 'IN_PROGRESS', 'keywords' => $caseTitle1]);
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_title' => $caseTitle2, 'case_status' => 'IN_PROGRESS', 'keywords' => $caseTitle2]);
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_title' => $caseTitle3, 'case_status' => 'IN_PROGRESS', 'keywords' => $caseTitle3]);

        $this->assertDatabaseCount('cases_participated', 20);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['pageSize' => 50]));
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => 'accident']));
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => 'proc']));
        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => 'registration       accident']));
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => 'insurance abe']));
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['search' => '(evaluation)']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_completed_cases_by_case_title(): void
    {
        $caseTitle1 = 'Accident Insurance Registration Process';
        $caseTitle2 = 'ABE Spring';
        $caseTitle3 = 'Credit Evaluation Process 123-abc';

        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'case_status' => 'COMPLETED', 'keywords' => $caseTitle1]);
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 10, ['case_title' => $caseTitle2, 'case_status' => 'COMPLETED', 'keywords' => $caseTitle2]);
        CaseControllerTest::createCasesParticipatedForUser($this->user->id, 5, ['case_title' => $caseTitle3, 'case_status' => 'COMPLETED', 'keywords' => $caseTitle3]);

        $this->assertDatabaseCount('cases_participated', 20);

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['pageSize' => 30]));
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => 'accident']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => 'ab']));
        $response->assertStatus(200);
        $response->assertJsonCount(15, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => 'credit evaluation']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => 'proc         spr']));
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => '(accident)']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.completed', ['search' => '(123-abc)']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_all_cases_special_by_dni(): void
    {
        $caseTitle1 = 'this is a ci 123456LP';
        $caseTitle2 = "John's Vacation";

        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'keywords' => $caseTitle1]);
        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle2, 'keywords' => $caseTitle2]);

        $this->assertDatabaseCount('cases_started', 10);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => '123456LP']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => "John's"]));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_all_cases_special_by_japanese_characters(): void
    {
        $caseTitle1 = '信用評価プロセス';

        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'keywords' => $caseTitle1]);

        $this->assertDatabaseCount('cases_started', 5);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => $caseTitle1]));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => '信用']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_all_cases_special_by_thai_characters(): void
    {
        $caseTitle1 = 'กระบวนการประเมินเครดิต';

        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'keywords' => $caseTitle1]);

        $this->assertDatabaseCount('cases_started', 5);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'กระบวนการ']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_search_all_cases_special_by_french_characters(): void
    {
        $caseTitle1 = 'Processus du crédit';

        CaseControllerTest::createCasesStartedForUser($this->user->id, 5, ['case_title' => $caseTitle1, 'keywords' => $caseTitle1]);

        $this->assertDatabaseCount('cases_started', 5);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'Processus']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'crédit']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => 'Processus crédit']));
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    protected function connectionsToTransact()
    {
        return [];
    }
}
