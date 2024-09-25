<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseControllerTest extends TestCase
{
    use RequestHelper;

    private function createUser(string $username, string $password = 'secret', string $status = 'ACTIVE'): User
    {
        return User::factory()->create([
            'username' => $username,
            'password' => Hash::make($password),
            'status' => $status,
        ]);
    }

    private function createCasesStartedForUser(int $userId, int $count = 1, $data = [])
    {
        return CaseStarted::factory()->count($count)->create(array_merge(['user_id' => $userId], $data));
    }

    private function createCasesParticipatedForUser(int $userId, int $count = 1, $data = [])
    {
        return CaseParticipated::factory()->count($count)->create(array_merge(['user_id' => $userId], $data));
    }

    public function test_get_all_cases(): void
    {
        $userA = $this->createUser('user_a');
        $cases = $this->createCasesStartedForUser($userA->id, 10);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
    }

    public function test_get_in_progress(): void
    {
        $userA = $this->createUser('user_a');
        $cases = $this->createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonMissing(['case_status' => 'COMPLETED']);
    }

    public function test_get_completed(): void
    {
        $userA = $this->createUser('user_a');
        $cases = $this->createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);

        $response = $this->apiCall('GET', route('api.1.1.cases.completed'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonFragment(['case_status' => 'COMPLETED']);
        $response->assertJsonMissing(['case_status' => 'IN_PROGRESS']);
    }

    public function test_get_all_cases_by_users(): void
    {
        $userA = $this->createUser('user_a');
        $userB = $this->createUser('user_b');

        $casesA = $this->createCasesStartedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesB = $this->createCasesStartedForUser($userB->id, 6, ['case_status' => 'COMPLETED']);
        $casesC = $this->createCasesStartedForUser($userA->id, 4, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));

        $total = $casesA->count() + $casesB->count() + $casesC->count();
        $response->assertStatus(200);
        $response->assertJsonCount($total, 'data');

        $totalUserA = $casesA->count() + $casesC->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['userId' => $userA->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($totalUserA, 'data');
        $response->assertJsonMissing(['user_id' => $userB->id]);

        $totalUserB = $casesB->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['userId' => $userB->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($totalUserB, 'data');
        $response->assertJsonMissing(['user_id' => $userA->id]);
    }

    public function test_get_all_cases_by_status(): void
    {
        $userA = $this->createUser('user_a');
        $userB = $this->createUser('user_b');

        $casesA = $this->createCasesStartedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);
        $casesB = $this->createCasesStartedForUser($userB->id, 6, ['case_status' => 'IN_PROGRESS']);
        $casesC = $this->createCasesStartedForUser($userA->id, 4, ['case_status' => 'COMPLETED']);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['status' => 'IN_PROGRESS']));
        $response->assertStatus(200);
        $response->assertJsonCount($casesB->count(), 'data');

        $totalCompleted = $casesA->count() + $casesC->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['status' => 'COMPLETED']));
        $response->assertStatus(200);
        $response->assertJsonCount($totalCompleted, 'data');
    }

    public function test_get_in_progress_by_user(): void
    {
        $userA = $this->createUser('user_a');
        $userB = $this->createUser('user_b');
        $userC = $this->createUser('user_c');
        $casesA = $this->createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesB = $this->createCasesParticipatedForUser($userB->id, 6, ['case_status' => 'IN_PROGRESS']);
        $casesC = $this->createCasesParticipatedForUser($userC->id, 4, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userA->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesA->count(), 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userB->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesB->count(), 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userC->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesC->count(), 'data');
    }

    public function test_search_all_cases_by_case_number(): void
    {
        $userA = $this->createUser('user_a');
        $this->createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $this->createCasesStartedForUser($userA->id, 1, ['case_number' => $caseNumber]);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['search' => $caseNumber]));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_get_all_cases_sort_by_case_number(): void
    {
        $userA = $this->createUser('user_a');
        $cases = $this->createCasesStartedForUser($userA->id, 10);
        $casesSorted = $cases->sortBy('case_number');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'case_number:asc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.case_number', $casesSorted->first()->case_number);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'case_number:desc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.case_number', $casesSorted->last()->case_number);
    }

    public function test_get_all_cases_sort_by_completed_at(): void
    {
        $userA = $this->createUser('user_a');
        $cases = $this->createCasesStartedForUser($userA->id, 10);
        $casesSorted = $cases->sortBy('completed_at');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'completed_at:asc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.completed_at', $casesSorted->first()->completed_at->format('Y-m-d H:i:s'));

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'completed_at:desc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.completed_at', $casesSorted->last()->completed_at->format('Y-m-d H:i:s'));
    }

    public function test_get_all_cases_sort_by_invalid_field(): void
    {
        $invalidField = 'invalid_field';

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => $invalidField]));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The sortBy must be a comma-separated list of field:asc|desc.');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => "$invalidField:asc"]));
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => "Sort by field $invalidField is not allowed."]);
    }

    public function test_get_all_cases_filter_by(): void
    {
        $userA = $this->createUser('user_a');
        $casesA = $this->createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $casesB = $this->createCasesStartedForUser($userA->id, 1, ['case_number' => $caseNumber, 'case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['filterBy' => ['case_number' => $caseNumber]]));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['case_number' => $caseNumber]);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['filterBy' => ['case_status' => 'IN_PROGRESS']]));
        $response->assertStatus(200);
        $total = $casesA->where('case_status', 'IN_PROGRESS')->count() + $casesB->where('case_status', 'IN_PROGRESS')->count();
        $response->assertJsonCount($total, 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonMissing(['case_status' => 'COMPLETED']);
    }

    public function test_get_all_cases_filter_by_invalid_field(): void
    {
        $invalidField = 'invalid_field';

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['filterBy' => '']));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The Filter by field must be an array.');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['filterBy' => [$invalidField => 'value']]));
        $response->assertStatus(422);
        $response->assertJsonPath('message', "Filter by field $invalidField is not allowed.");
    }
}
